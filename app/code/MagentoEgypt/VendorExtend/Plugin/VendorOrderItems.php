<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin;

use Vnecoms\VendorsSales\Block\Adminhtml\Vorder\View\Items;

class VendorOrderItems
{
    /**
     * Use the vendor order model's assignment fallback for legacy order items.
     *
     * @param Items $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemsCollection(Items $subject, array $result): array
    {
        return $subject->getOrder()->getAllItems();
    }
}