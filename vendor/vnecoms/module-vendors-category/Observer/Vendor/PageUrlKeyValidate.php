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
class PageUrlKeyValidate implements ObserverInterface
{

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getEvent()->getTransport();
        $errors = $transport->getErrors();
        $vendorId = $transport->getVendorId();
        $urlKey = $transport->getUrlKey();
        $type = $transport->getType();
        if($type != "category"){
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $resourceModel = $om->create('Vnecoms\VendorsCategory\Model\ResourceModel\Category');
            $flag = $resourceModel->loadByUrlKeyAndVendorId($vendorId,$urlKey);
            if($flag){
                $errors[] = __("The value specified in the URL Key field would generate a URL that already exists in Category.");
            }
            $transport->setErrors($errors);
        }
    }

}