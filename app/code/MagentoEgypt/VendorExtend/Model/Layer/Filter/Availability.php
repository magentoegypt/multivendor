<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Layer\Filter;

/**
 * "Availability" — the prototype's In Stock Only / On Sale pair.
 *
 * In stock reads `cataloginventory_stock_status`. On sale is decided by the
 * PRICE INDEX rather than the `special_price` attribute: this catalogue has
 * store-scoped special_from/to rows that leave a special price set but not in
 * force (that trap already cost this project a cycle), and the index is the only
 * place that reflects what a shopper actually pays today.
 */
class Availability extends AbstractIdFilter
{
    private const IN_STOCK = 'in_stock';
    private const ON_SALE  = 'on_sale';

    /**
     * Set as a PROPERTY, not in _construct(). AbstractFilter extends
     * DataObject, which has no _construct() hook — only AbstractModel does —
     * so a _requestVar assigned there is never run. It stayed null, and
     * `$request->getParam(null)` then returned null on every request: the
     * block rendered its options and filtered nothing. Core's own Price and
     * Category filters declare it exactly this way.
     */
    protected $_requestVar = 'availability';

    public function getName(): \Magento\Framework\Phrase|string
    {
        return __('Availability');
    }

    protected function hmOptions(): array
    {
        return [
            self::IN_STOCK => (string) __('In Stock Only'),
            self::ON_SALE  => (string) __('On Sale'),
        ];
    }

    protected function hmIdsFor(string $value): array
    {
        $conn = $this->hmResource->getConnection();

        if ($value === self::IN_STOCK) {
            $select = $conn->select()
                ->from(['s' => $this->hmResource->getTableName('cataloginventory_stock_status')], ['product_id'])
                ->where('s.stock_status = ?', 1)
                ->distinct(true);

            return array_map('intval', $conn->fetchCol($select));
        }

        if ($value === self::ON_SALE) {
            $websiteId = (int) $this->_storeManager->getStore()->getWebsiteId();
            $select = $conn->select()
                ->from(['p' => $this->hmResource->getTableName('catalog_product_index_price')], ['entity_id'])
                ->where('p.website_id = ?', $websiteId)
                ->where('p.final_price < p.price')
                ->distinct(true);

            return array_map('intval', $conn->fetchCol($select));
        }

        return [];
    }
}
