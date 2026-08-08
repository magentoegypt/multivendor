<?php
/**
 * Hub Market — countdown to the end of the current deals.
 *
 * STANDALONE ON PURPOSE. The first attempt made this a child block of
 * TodaysDeals, which extends CatalogWidget's ProductsList — so it inherited a
 * dependency on a `conditions_encoded` argument it was never given, and the rule
 * parsing fataled. A countdown needs one date; it has no business inheriting a
 * product-list block.
 *
 * This extends plain Template and asks the database directly, so it cannot be
 * broken by anything happening to the product rail beside it.
 *
 * The deadline is the earliest special_to_date among offers that are live right
 * now. If nothing is on offer, or offers have no end date, it renders nothing —
 * a countdown with no real expiry is a dark pattern, and this one is derived
 * from the same prices the customer is charged, so it cannot drift from them.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Block;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class DealsCountdown extends Template
{
    private ResourceConnection $resource;
    private ?string $resolved = null;
    private bool $resolvedRun = false;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        array $data = []
    ) {
        $this->resource = $resource;
        parent::__construct($context, $data);
    }

    /**
     * ISO-8601 end of the soonest-expiring live offer, or null.
     */
    public function getDeadline(): ?string
    {
        if ($this->resolvedRun) {
            return $this->resolved;
        }
        $this->resolvedRun = true;

        $conn  = $this->resource->getConnection();
        $dec   = $this->resource->getTableName('catalog_product_entity_decimal');
        $dt    = $this->resource->getTableName('catalog_product_entity_datetime');
        $eav   = $this->resource->getTableName('eav_attribute');
        $etype = $this->resource->getTableName('eav_entity_type');

        // Plain closure, NOT `static fn`: a static closure cannot bind $this,
        // which is precisely what took the homepage down once already.
        $attr = fn (string $code) => $conn->select()
            ->from(['a' => $eav], ['attribute_id'])
            ->where('a.attribute_code = ?', $code)
            ->where('a.entity_type_id = (SELECT entity_type_id FROM ' . $etype
                . ' WHERE entity_type_code = \'catalog_product\')');

        // Store-local "today": a deal ending today should stay live all day, and
        // SQL NOW() would cut it at midnight UTC regardless of the store timezone
        // (this install runs Asia/Riyadh).
        $today = $this->_localeDate->date()->format('Y-m-d');

        try {
            $select = $conn->select()
                ->from(['sp' => $dec], [])
                ->join(['td' => $dt], 'td.entity_id = sp.entity_id AND td.attribute_id = ('
                    . $attr('special_to_date') . ')', ['ends' => 'MIN(td.value)'])
                ->where('sp.attribute_id = (' . $attr('special_price') . ')')
                ->where('sp.value IS NOT NULL')
                ->where('sp.value > 0')
                ->where('DATE(td.value) >= ?', $today);

            $ends = $conn->fetchOne($select);
        } catch (\Throwable $e) {
            // A countdown is decoration. It must never take the page down.
            $this->_logger->warning('Hub Market deals countdown: ' . $e->getMessage());
            return $this->resolved = null;
        }

        if (!$ends) {
            return $this->resolved = null;
        }

        return $this->resolved = (new \DateTime((string) $ends))
            ->format(\DateTimeInterface::ATOM);
    }

    public function getCacheKeyInfo(): array
    {
        return [
            'HM_DEALS_COUNTDOWN',
            $this->_storeManager->getStore()->getId(),
            // Re-render when the day rolls over so an expired deal drops out.
            $this->_localeDate->date()->format('Y-m-d'),
        ];
    }

    protected function getCacheLifetime(): ?int
    {
        return 300;
    }
}
