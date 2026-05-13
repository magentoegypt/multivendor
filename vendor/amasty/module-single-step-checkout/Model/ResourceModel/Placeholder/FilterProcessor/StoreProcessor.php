<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model\ResourceModel\Placeholder\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;

class StoreProcessor implements CustomFilterInterface
{
    /**
     * @param Filter $filter
     * @param AbstractDb $collection
     *
     * @return bool
     */
    public function apply(Filter $filter, AbstractDb $collection): bool
    {
        $storeId = (int)$filter->getValue();
        $collection->addStoreFilter($storeId);

        return true;
    }
}
