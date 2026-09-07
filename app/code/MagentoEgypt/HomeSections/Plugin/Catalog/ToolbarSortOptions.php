<?php
/**
 * "Top Rated" and "Newest" in the PLP sort control — QA cycle 2, item 2-A.
 *
 * The reference's dropdown reads Featured / Price: Low to High / Price: High
 * to Low / Top Rated / Newest. The theme's sorter template deliberately left
 * the last two out in cycle 1 because the collection could not honour them
 * (see Magento_Catalog::product/list/toolbar/sorter.phtml — offering an order
 * the collection ignores is worse than offering fewer). This plugin is the
 * missing capability, so the template now offers them.
 *
 * BOTH orders are applied on the SQL side by the around plugin, never through
 * core's setOrder():
 *
 * - "created_at" LOOKS safe to hand to core — it is a static entity column —
 *   but on a category page the collection is the CatalogSearch fulltext one,
 *   and core forwards the sort into the OpenSearch query, where this install
 *   maps created_at as TEXT. OpenSearch then refuses to sort ("fielddata is
 *   disabled on text fields"), ALL SHARDS FAIL, and the listing renders empty
 *   with a 200 — the exact storefront-search failure class this project has
 *   already been bitten by twice (see the search-attribute guard history).
 *   Verified from var/log/exception.log before this note was written.
 *
 * - "top_rated" has NO core sort at all — it needs a join against
 *   review_entity_summary.
 *
 * So the plugin swaps the memorized order to 'position' while core runs (the
 * pager and limits still get wired, and OpenSearch is only ever asked for the
 * safe position sort), restores it afterwards (the select must show the right
 * selection), and applies the real ordering to the collection's own SELECT —
 * which is the MySQL side of the fulltext collection, where both sorts are
 * cheap and cannot take the search cluster down.
 *
 * The summary join is STORE-SCOPED. This catalog keeps its rating aggregates
 * per store view (stores 1 and 3), and that scoping has bitten this project
 * twice — see the review_store/rating_store history. A missing row sorts as
 * NULL, which MySQL places last under DESC, exactly where an unrated product
 * belongs in a "Top Rated" listing.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Plugin\Catalog;

use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Store\Model\StoreManagerInterface;

class ToolbarSortOptions
{
    private const ORDER_TOP_RATED = 'top_rated';
    private const ORDER_NEWEST    = 'created_at';

    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @param array<string, string> $result
     * @return array<string, string>
     */
    public function afterGetAvailableOrders(Toolbar $subject, array $result): array
    {
        $result[self::ORDER_TOP_RATED] = (string) __('Top Rated');
        $result[self::ORDER_NEWEST]    = (string) __('Newest');

        return $result;
    }

    /**
     * @param \Magento\Framework\Data\Collection $collection
     */
    public function aroundSetCollection(Toolbar $subject, callable $proceed, $collection): Toolbar
    {
        /* Memorizes the validated order into _current_grid_order as a side effect. */
        $order = (string) $subject->getCurrentOrder();

        if (!in_array($order, [self::ORDER_TOP_RATED, self::ORDER_NEWEST], true)
            || !$collection instanceof \Magento\Catalog\Model\ResourceModel\Product\Collection
        ) {
            return $proceed($collection);
        }

        $subject->setData('_current_grid_direction', 'desc');
        $subject->setData('_current_grid_order', 'position');
        $result = $proceed($collection);
        $subject->setData('_current_grid_order', $order);

        $select = $collection->getSelect()->reset(\Magento\Framework\DB\Select::ORDER);

        if ($order === self::ORDER_NEWEST) {
            $select->order('e.created_at DESC')->order('e.entity_id DESC');

            return $result;
        }

        /*
         * The toolbar is rendered TWICE per page (top and bottom of the grid)
         * and both instances call setCollection() on the SAME collection, so a
         * second unguarded joinLeft died with "correlation name 'hm_res' more
         * than once". Join once; the order re-application is idempotent.
         */
        $fromParts = $select->getPart(\Magento\Framework\DB\Select::FROM);
        if (!isset($fromParts['hm_res'])) {
            $connection = $collection->getConnection();
            $select->joinLeft(
                ['hm_res' => $collection->getResource()->getTable('review_entity_summary')],
                implode(' AND ', [
                    'hm_res.entity_pk_value = e.entity_id',
                    'hm_res.entity_type = 1',
                    $connection->quoteInto('hm_res.store_id = ?', (int) $this->storeManager->getStore()->getId()),
                ]),
                []
            );
        }
        $select->order('hm_res.rating_summary DESC')
            ->order('hm_res.reviews_count DESC')
            ->order('e.entity_id DESC');

        return $result;
    }
}
