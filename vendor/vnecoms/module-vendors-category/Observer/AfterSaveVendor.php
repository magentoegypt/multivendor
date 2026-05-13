<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\Vendors\Model\Vendor;

class AfterSaveVendor implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // TODO: Implement execute() method.

        $category = \Magento\Framework\App\ObjectManager::getInstance()->get('Vnecoms\VendorsCategory\Model\Category');
        $vendor = $observer->getData('vendor');

        $category->setData([
            'real_category_id' => $vendor->getId(),
            'name' => __('Root'),
            'path' => $category->getId(),
            'is_active' => 1,
            'vendor_id' => $vendor->getId(),
            'parent_id' => 0,
            'status' => 1,
            'position' => $vendor->getId(),
            'is_hide_product' => 0,
        ])->save();
    }
}
