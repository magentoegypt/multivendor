<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCommission\Observer;

use Magento\Framework\Event\ObserverInterface;

class CalculateCommission implements ObserverInterface
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Vnecoms\VendorsCommission\Helper\Data
     */
    protected $_ruleHelper;

    /**
     * CalculateCommission constructor.
     * @param \Vnecoms\VendorsCommission\Helper\Data $ruleHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Vnecoms\VendorsCommission\Helper\Data $ruleHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_ruleHelper = $ruleHelper;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $commissionObj = $observer->getCommission();
        $invoiceItem   = $observer->getInvoiceItem();
        $product       = $observer->getProduct();
        $vendor        = $observer->getVendor();
        $vendorGroupId = $vendor->getGroupId();
        $invoice       = $invoiceItem->getInvoice();
        $storeId       = $invoice->getStoreId();
        $websiteId     = $this->_storeManager->getStore($storeId)->getWebsiteId();

        $fees = $this->_ruleHelper->getFeeCommission(
            $vendorGroupId,
            $websiteId,
            $product,
            $invoiceItem,
            $invoice->getOrder(),
            $commissionObj->getFee()
        );

        if (isset($fees['fee']) && $fees['fee']) {
            $commissionObj->setFee($fees['fee']);
            $commissionObj->setDescriptions($fees['description']);
        }

        return $this;
    }
}
