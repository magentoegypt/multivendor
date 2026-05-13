<?php
/**
 * Copyright © Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsProduct\Plugin\Quote\Item;

use Vnecoms\VendorsProduct\Helper\Data as ProductHelper;
use Magento\Framework\Event\Observer;
use Magento\CatalogInventory\Helper\Data;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class TypePlugin
 */
class QuantityValidator
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
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productloader;

    /**
     * QuantityValidator constructor.
     * @param ProductHelper $productHelper
     * @param \Vnecoms\Vendors\Helper\Data $vendorData
     * @param \Magento\Catalog\Model\ProductFactory $_productloader
     */
    public function __construct(
        ProductHelper $productHelper,
        \Vnecoms\Vendors\Helper\Data $vendorData,
        \Magento\Catalog\Model\ProductFactory $_productloader
    ) {
        $this->_productHelper = $productHelper;
        $this->vendorData = $vendorData;
        $this->_productloader = $_productloader;
    }

    /**
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject
     * @param Observer $observer
     */
    public function beforeValidate(
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject,
        Observer $observer
    ) {
        /* @var $quoteItem Item */
        $quoteItem = $observer->getEvent()->getItem();
        if (!$quoteItem ||
            !$quoteItem->getProductId() ||
            !$quoteItem->getQuote()
        ) {
            return;
        }
        $product = $quoteItem->getProduct();

        $productApprovalStatus = $this->_productHelper->getAllowedApprovalStatus();
        $notActiveVendorIds = $this->vendorData->getNotActiveVendorIds();

        $product =  $this->_productloader->create()->load($product->getId());

        if (!in_array($product->getData("approval"), $productApprovalStatus)
            || in_array($product->getData("vendor_id"), $notActiveVendorIds)
        ) {
            $quoteItem->getQuote()->addErrorInfo(
                'stock',
                'cataloginventory',
                Data::ERROR_QTY,
                __('Some of the products are invalid.')
            );
            throw new LocalizedException(__('The Product is invalid. Verify the product and try again'));
        }
    }
}
