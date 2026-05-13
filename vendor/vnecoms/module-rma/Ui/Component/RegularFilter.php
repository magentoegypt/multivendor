<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\RMA\Ui\Component;

use Magento\Framework\Data\Collection;
use Magento\Framework\Api\Filter;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface;

/**
 * Class RegularFilter
 */
class RegularFilter implements FilterApplierInterface
{
    /**
     * Apply regular filters like collection filters
     *
     * @param Collection $collection
     * @param Filter $filter
     * @return void
     */
    public function apply(Collection $collection, Filter $filter)
    {
        $collection->addFieldToFilter($filter->getField(), [$filter->getConditionType() => $filter->getValue()]);
    }
}
