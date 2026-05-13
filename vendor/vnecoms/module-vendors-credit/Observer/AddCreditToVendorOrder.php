<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCredit\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Helper\Data as VendorConfig;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Psr\Log\LoggerInterface;

class AddCreditToVendorOrder implements ObserverInterface
{
    /**
     * Add multiple vendor order row for each vendor.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $vendorOrderData = $observer->getOrderData();
        $items = $observer->getItems() ;
        $creditAmount = 0;
        $baseCreditAmount = 0;
        foreach ($items as $item) {
            $creditAmount += $item->getData('credit_amount');
            $baseCreditAmount += $item->getData('base_credit_amount');
        }

        if ($creditAmount > $vendorOrderData->getData("grand_total")) {
            $creditAmount = $vendorOrderData->getData("grand_total");
            $baseCreditAmount = $vendorOrderData->getData("base_grand_total");
        }

        $vendorOrderData->setData('credit_amount', $creditAmount);
        $vendorOrderData->setData('base_credit_amount', $baseCreditAmount);
        $vendorOrderData->setData('grand_total', $vendorOrderData->getData("grand_total") - $creditAmount);
        $vendorOrderData->setData('base_grand_total', $vendorOrderData->getData("base_grand_total") - $baseCreditAmount);
        return $this;
    }

}
