<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProduct\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsProduct\Helper\Data as ProductHelper;
use Magento\Framework\Exception\LocalizedException;

class ValidateBeforeAddToCart implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $_productHelper;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $vendorData;

    /**
     * ValidateBeforeAddToCart constructor.
     * @param ProductHelper $productHelper
     * @param \Vnecoms\Vendors\Helper\Data $vendorData
     */
    public function __construct(
        ProductHelper $productHelper,
        \Vnecoms\Vendors\Helper\Data $vendorData
    ) {
        $this->_productHelper = $productHelper;
        $this->vendorData = $vendorData;
    }

    /**
     * Save product data for all child products
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $productApprovalStatus = $this->_productHelper->getAllowedApprovalStatus();
        $product = $observer->getData("product");
        if (!in_array($product->getData("approval"), $productApprovalStatus)) {
            throw new LocalizedException(__('The product does not exist.'));
        }
        $notActiveVendorIds = $this->vendorData->getNotActiveVendorIds();
        if (in_array($product->getData("vendor_id"), $notActiveVendorIds)) {
            throw new LocalizedException(__('The product does not exist.'));
        }
    }
}
