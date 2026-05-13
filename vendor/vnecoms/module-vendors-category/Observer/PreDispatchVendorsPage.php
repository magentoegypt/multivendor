<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\Vendors\Model\Vendor;

class PreDispatchVendorsPage implements ObserverInterface
{
    protected $registry;

    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
    }

    public function getVendor()
    {
        return $this->registry->registry('vendor');
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->getVendor() == null) {
            return;
        }
        if ($this->registry->registry('current_root_cat')) {
            return;
        }
        $categoryModel = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Vnecoms\VendorsCategory\Model\Category');

        $rootId = $categoryModel->getRootId($this->getVendor()->getId());

        $rootCategory = $categoryModel->load($rootId);

        $this->registry->register('current_root_cat', $rootCategory);
    }
}
