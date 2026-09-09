<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Model\Layer;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Category as CategoryLayer;
use Magento\Catalog\Model\Layer\FilterList;
use Magento\Framework\ObjectManagerInterface;
use MagentoEgypt\VendorExtend\Model\Layer\Filter\Availability;
use MagentoEgypt\VendorExtend\Model\Layer\Filter\Rating;
use MagentoEgypt\VendorExtend\Model\Layer\Filter\Vendor;

/**
 * Appends the three QA03 item 2-A filters to the category panel.
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
        //  Category pages only. The search results page has its own panel and
        //  was not part of this ticket.
        if (!$layer instanceof CategoryLayer) {
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
