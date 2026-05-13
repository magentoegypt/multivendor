<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\ResourceModel\Field\Collection;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\ResourceModel\Field\Collection;

class FilterByAttributeAndStore
{
    /**
     * @param Collection $collection
     * @param int $attributeId
     * @param int[]|string[] $storeIds
     * @return void
     */
    public function execute(Collection $collection, int $attributeId, array $storeIds): void
    {
        $collection->addFieldToFilter(Field::ATTRIBUTE_ID, $attributeId);
        $collection->addFieldToFilter(Field::STORE_ID, ['in' => $storeIds]);
    }
}
