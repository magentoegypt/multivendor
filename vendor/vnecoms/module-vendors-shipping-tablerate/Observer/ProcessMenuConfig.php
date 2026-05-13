<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShippingTableRate\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Helper\Data;

class ProcessMenuConfig implements ObserverInterface
{
    /**
     * Add multiple vendor order row for each vendor.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $resource = $observer->getResource();
        $result = $observer->getResult();

        if($resource == "Vnecoms_VendorsShippingTableRate::shipping"){
            $config = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\Framework\App\Config\ScopeConfigInterface');
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
            $configVal = $config->getValue("carriers/vtablerate/active",$storeScope);
            $configShipping = $config->getValue("carriers/vendor_multirate/active",$storeScope);
            if(!$configVal || !$configShipping) $result->setIsAllowed(false);
        }

        return $this;
    }
}
