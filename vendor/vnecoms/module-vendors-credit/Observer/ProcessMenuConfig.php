<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCredit\Observer;

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

        $config = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Magento\Framework\App\Config\ScopeConfigInterface');
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;

        if($resource == "Vnecoms_VendorsCredit::sales_transactions"){
            $configVal = $config->getValue("vendors/credit/enable_credit",$storeScope);
            if(!$configVal) $result->setIsAllowed(false);
        }

        if($resource == "Vnecoms_VendorsCredit::sales_transactions_pending"){
            $helperData = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Vnecoms\VendorsCredit\Helper\Data');
            $configVal = $config->getValue("vendors/credit/enable_credit",$storeScope);
            if(!$configVal || !$helperData->isEnabledEscrowTransaction()) $result->setIsAllowed(false);
        }

        return $this;
    }
}
