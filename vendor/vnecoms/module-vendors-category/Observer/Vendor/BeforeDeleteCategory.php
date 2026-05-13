<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Observer\Vendor;

use Magento\Framework\Event\ObserverInterface;

/**
 * event for prepare save, see in Save Action
 * Class CategorySaveRewritesHistorySetterObserver.
 */
class BeforeDeleteCategory implements ObserverInterface
{
    protected $vendorSession;

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // TODO: Implement execute() method.
        $category = $observer->getCategory();
    }

    public function getVendor()
    {
        if ($this->vendorSession) {
            return $this->vendorSession->getVendor();
        } else {
            $this->vendorSession = $this->_objectManager->get('Vnecoms\Vendors\Model\Session');

            return $this->vendorSession->getVendor();
        }
    }
}
