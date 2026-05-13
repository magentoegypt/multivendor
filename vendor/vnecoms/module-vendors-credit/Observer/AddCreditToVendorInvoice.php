<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCredit\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddCreditToVendorInvoice implements ObserverInterface
{
    /**
     * Add multiple vendor order row for each vendor.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $vendorInvoiceData = $observer->getInvoiceData();
        $items = $observer->getItems() ;
        $creditAmount = 0;
        $baseCreditAmount = 0;
        foreach ($items as $item) {
            $creditAmount += $item->getData('credit_amount');
            $baseCreditAmount += $item->getData('base_credit_amount');
        }

        if ($creditAmount > $vendorInvoiceData->getData("grand_total")) {
            $creditAmount = $vendorInvoiceData->getData("grand_total");
            $baseCreditAmount =  $vendorInvoiceData->getData("base_grand_total");
        }

        $vendorInvoiceData->setData('credit_amount', $creditAmount);
        $vendorInvoiceData->setData('base_credit_amount', $baseCreditAmount);
        $vendorInvoiceData->setData('grand_total', $vendorInvoiceData->getData("grand_total") - abs($creditAmount));
        $vendorInvoiceData->setData('base_grand_total', $vendorInvoiceData->getData("base_grand_total") - abs($baseCreditAmount));
        return $this;
    }

}
