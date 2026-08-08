<?php
/**
 * Hub Market — "Today's Deals".
 *
 * Driven entirely by real catalogue data: products whose special_price is active
 * RIGHT NOW, ranked by discount depth. Nothing is faked — if no product is on
 * offer the section renders nothing rather than inventing a sale, and it lights
 * up again by itself the moment a merchandiser sets a special price in admin.
 *
 * The countdown deadline is the earliest special_to_date among the products
 * shown, so the timer reflects when the offer actually ends. A countdown with no
 * real expiry is a dark pattern, and this one cannot drift from the prices.
 *
 * Extends BestSellers only to inherit the _beforeToHtml hand-off — see that
 * class for why overriding createCollection() does not work here (three modules
 * plugin it and discard the result).
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Block;

class TodaysDeals extends BestSellers
{
    /** @var string|null earliest special_to_date among the shown products */
    private ?string $deadline = null;

    /**
     * Product ids currently on special, deepest discount first.
     *
     * special_from_date / special_to_date are compared against the store's
     * "today" rather than SQL NOW(): a deal that ends today should still be
     * live all day, and NOW() would cut it off at midnight UTC regardless of the
     * store's timezone (this install runs Asia/Riyadh).
     *
     * @return int[]
     */
    protected function getRankedProductIds(): array
    {
        $limit = (int) ($this->getData('products_count') ?: 4);
        $conn  = $this->hmResource->getConnection();

        $entity = $this->hmResource->getTableName('catalog_product_entity');
        $dec    = $this->hmResource->getTableName('catalog_product_entity_decimal');
        $dt     = $this->hmResource->getTableName('catalog_product_entity_datetime');
        $eav    = $this->hmResource->getTableName('eav_attribute');
        $etype  = $this->hmResource->getTableName('eav_entity_type');

        // NOT `static fn`: a static closure cannot bind $this, and this one
        // dereferences $this->hmResource. That failed at runtime with
        // "Using $this when not in object context" — which PHP only raises when
        // the closure is actually invoked, so php -l and di:compile both passed
        // and the homepage 500'd instead.
        $attr = fn (string $code) => $conn->select()
            ->from(['a' => $eav], ['attribute_id'])
            ->where('a.attribute_code = ?', $code)
            ->where('a.entity_type_id = (SELECT entity_type_id FROM ' . $etype . ' WHERE entity_type_code = \'catalog_product\')');

        $today = $this->_localeDate->date()->format('Y-m-d');

        $select = $conn->select()
            ->from(['e' => $entity], ['entity_id'])
            ->join(['sp' => $dec], 'sp.entity_id = e.entity_id AND sp.attribute_id = (' . $attr('special_price') . ')',
                ['special' => 'sp.value'])
            ->join(['p' => $dec], 'p.entity_id = e.entity_id AND p.attribute_id = (' . $attr('price') . ')',
                ['price' => 'p.value'])
            ->joinLeft(['fd' => $dt], 'fd.entity_id = e.entity_id AND fd.attribute_id = (' . $attr('special_from_date') . ')',
                ['from_date' => 'fd.value'])
            ->joinLeft(['td' => $dt], 'td.entity_id = e.entity_id AND td.attribute_id = (' . $attr('special_to_date') . ')',
                ['to_date' => 'td.value'])
            ->where('sp.value IS NOT NULL')
            ->where('sp.value > 0')
            ->where('p.value > sp.value')                       // a real reduction
            ->where('fd.value IS NULL OR DATE(fd.value) <= ?', $today)
            ->where('td.value IS NULL OR DATE(td.value) >= ?', $today)
            ->group('e.entity_id')
            // deepest percentage discount first
            ->order(new \Magento\Framework\DB\Sql\Expression('((p.value - sp.value) / p.value) DESC'))
            ->limit($limit * 3);

        $rows = $conn->fetchAll($select);
        if (!$rows) {
            return [];
        }

        $ends = array_filter(array_column($rows, 'to_date'));
        $this->deadline = $ends ? min($ends) : null;

        return array_map('intval', array_column($rows, 'entity_id'));
    }

    /**
     * ISO-8601 deadline for the countdown, or null when no offer has an end date.
     */
    public function getDeadline(): ?string
    {
        if ($this->deadline === null) {
            $this->getRankedProductIds();
        }
        return $this->deadline ? (new \DateTime($this->deadline))->format(\DateTimeInterface::ATOM) : null;
    }

    public function getCacheKeyInfo()
    {
        $info = parent::getCacheKeyInfo();
        $info[] = 'HM_TODAYS_DEALS';
        // Date in the key so the section re-renders when a deal starts or ends.
        $info[] = $this->_localeDate->date()->format('Y-m-d');
        return $info;
    }
}
