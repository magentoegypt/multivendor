<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Layer\Filter;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\Filter\DataProvider\Price as PriceDataProvider;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Pager;

/**
 * Shared base for the three QA03 item 2-A filters — Minimum Rating, Availability
 * and Vendors.
 *
 * None of the three is a product attribute, so none can be a normal layered-nav
 * filter: the rating lives in `review_entity_summary`, availability in
 * `cataloginventory_stock_status` and `catalog_product_index_price`, and the
 * seller in `catalog_product_entity.vendor_id` (a STATIC column, not EAV) joined
 * to `ves_vendor_entity.company`.
 *
 * They therefore resolve to a PRODUCT ID LIST in SQL and narrow the collection
 * with `addIdFilter()`, rather than adding a field to the search request. That
 * matters on this install: the category collection is OpenSearch-backed through
 * Mageplaza's fulltext collection, and pushing an unindexed field into that
 * request is how this project previously took out all storefront search. An id
 * list is applied to the collection's own SELECT and cannot reach OpenSearch.
 */
abstract class AbstractIdFilter extends AbstractFilter
{
    protected ResourceConnection $hmResource;

    /** @var int[]|null Product ids in the CURRENT result set, memoised per request. */
    private ?array $hmScope = null;

    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        Layer\Filter\Item\DataBuilder $itemDataBuilder,
        ResourceConnection $resource,
        array $data = []
    ) {
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $data);
        $this->hmResource = $resource;
    }

    //  NOTE: getName() is NOT redeclared abstract here. AbstractFilter defines
    //  it concretely — it reads the label off the ATTRIBUTE model, which these
    //  filters do not have — and PHP will not let a subclass turn a concrete
    //  parent method abstract. Each filter simply overrides it.

    /**
     * @return array<string, string> option value => label
     */
    abstract protected function hmOptions(): array;

    /**
     * Product ids matching one option value.
     *
     * @return int[]
     */
    abstract protected function hmIdsFor(string $value): array;

    /**
     * @inheritDoc
     */
    public function apply(RequestInterface $request)
    {
        $value = $request->getParam($this->_requestVar);
        
        if ($value === null || $value === '' || is_array($value)) {
            return $this;
        }
        $value = (string) $value;

        if (!array_key_exists($value, $this->hmOptions())) {
            return $this;
        }

        $ids = $this->hmIdsFor($value);

        //  Written onto the collection's own SELECT rather than through
        //  addIdFilter()/addFieldToFilter(). On this install the category
        //  collection is Mageplaza's fulltext collection, whose
        //  addFieldToFilter() pushes the field into the OpenSearch search
        //  criteria — `entity_id` is not a field in that request, so the filter
        //  was accepted and then silently dropped (the panel filtered nothing;
        //  five products in, five products out). The SELECT is the SQL that
        //  hydrates whatever the engine returned, so an id list applied there
        //  intersects with the search result instead of being handed to it.
        //
        //  An empty match must mean "no products", not "no filter" — otherwise
        //  selecting a facet with nothing behind it silently shows everything.
        $this->getLayer()->getProductCollection()
            ->getSelect()
            ->where('e.entity_id IN (?)', $ids ?: [0]);

        $this->getLayer()->getState()->addFilter(
            $this->_createItem($this->hmOptions()[$value], $value)
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function _getItemsData()
    {
        $counts = $this->hmCounts($this->hmScopeIds());

        foreach ($this->hmOptions() as $value => $label) {
            $count = (int) ($counts[(string) $value] ?? 0);

            //  Magento's filter template renders a zero-count option as plain
            //  text rather than a link, so an option nothing matches is simply
            //  not offered.
            if ($count < 1) {
                continue;
            }
            $this->itemDataBuilder->addItemData((string) $label, $value, $count);
        }

        return $this->itemDataBuilder->build();
    }

    /**
     * How many of the current results each option would keep.
     *
     * One query per option by default, which is fine for the two- and
     * four-option blocks. Vendors overrides it with a single grouped query —
     * twenty-one sellers would otherwise be twenty-one round trips on every
     * category page.
     *
     * @param int[] $scopeIds
     * @return array<string, int>
     */
    protected function hmCounts(array $scopeIds): array
    {
        $out = [];

        foreach (array_keys($this->hmOptions()) as $value) {
            $out[(string) $value] = count(array_intersect($scopeIds, $this->hmIdsFor((string) $value)));
        }

        return $out;
    }

    /**
     * The ids the shopper is currently looking at, so counts describe THIS page
     * rather than the whole catalogue.
     *
     * @return int[]
     */
    protected function hmScopeIds(): array
    {
        if ($this->hmScope === null) {
            try {
                $this->hmScope = array_map('intval', $this->getLayer()->getProductCollection()->getAllIds());
            } catch (\Throwable $e) {
                $this->hmScope = [];
            }
        }

        return $this->hmScope;
    }

    protected function hmStoreId(): int
    {
        return (int) $this->_storeManager->getStore()->getId();
    }
}
