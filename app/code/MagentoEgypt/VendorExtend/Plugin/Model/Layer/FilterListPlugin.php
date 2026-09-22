<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Model\Layer;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Category as CategoryLayer;
use Magento\Catalog\Model\Layer\FilterList;
use Magento\Catalog\Model\Layer\Search as SearchLayer;
use Magento\Framework\ObjectManagerInterface;
use MagentoEgypt\VendorExtend\Model\Layer\Filter\Availability;
use MagentoEgypt\VendorExtend\Model\Layer\Filter\Rating;
use MagentoEgypt\VendorExtend\Model\Layer\Filter\Vendor;

/**
 * Appends the three QA03 item 2-A filters to the category AND search panels.
 *
 * `FilterList::getFilters()` builds the category filter and then one filter per
 * filterable ATTRIBUTE; there is no hook for a filter that is not an attribute,
 * and its `filterTypes` map is only ever read to pick a class for a backend
 * type. Appending to the returned list is the supported way in.
 */
class FilterListPlugin
{
    /** @var array<int, class-string> */
    private const EXTRA = [Rating::class, Availability::class, Vendor::class];

    public function __construct(
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * @param  FilterList $subject
     * @param  array      $result
     * @return array
     */
    public function afterGetFilters(FilterList $subject, array $result, Layer $layer): array
    {
        /*
         * Category AND search. Originally category-only, on the grounds that the
         * search results page "has its own panel" — but that panel is built from
         * the same FilterList, so it simply rendered without these three while the
         * category page had them. The two pages disagreed, which is what this
         * reverses.
         *
         * Safe on the search layer specifically because none of the three touch
         * the layer's type, and AbstractIdFilter::apply() writes its id list onto
         * the collection's own SELECT rather than through addFieldToFilter() —
         * the search layer's collection is a fulltext collection, and
         * addFieldToFilter() on one of those pushes `entity_id` into the
         * OpenSearch criteria, where it is accepted and then silently dropped.
         * That is the same reason the category collection needed the SELECT
         * route, so the mechanism already handles this case.
         *
         * Other layers (a widget's, a vendor microsite's) still return early.
         */
        if (!$layer instanceof CategoryLayer && !$layer instanceof SearchLayer) {
            return $result;
        }

        //  getFilters() memoises its list and is called more than once per
        //  request, so the plugin has to be idempotent or the panel gains a
        //  duplicate block on every call.
        foreach ($result as $existing) {
            if ($existing instanceof Rating) {
                return $result;
            }
        }

        foreach (self::EXTRA as $class) {
            try {
                $result[] = $this->objectManager->create($class, ['layer' => $layer]);
            } catch (\Throwable $e) {
                //  A filter that cannot be built must not take the category page
                //  down with it.
                continue;
            }
        }

        return $result;
    }
}
