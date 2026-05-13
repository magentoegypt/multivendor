<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Helper\Data as VendorConfig;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Psr\Log\LoggerInterface;

class UpdateShippingInfo implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $_vendorConfig;
    
    /**
     * @var PriceCurrencyInterface
     */
    protected $_priceCurrency;
    
    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Tax module helper
     *
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManage;
    
    /**
     * @var \Vnecoms\VendorsShipping\Helper\Data
     */
    protected $helper;

    /**
     * @var \Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping
     */
    protected $resourceQuoteShipping;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * UpdateShippingInfo constructor.
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Vnecoms\VendorsShipping\Helper\Data $helper
     * @param PriceCurrencyInterface $priceCurrency
     * @param VendorConfig $vendorConfig
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Module\Manager $moduleManage
     * @param \Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping $resourceQuoteShipping
     * @param \Magento\Tax\Model\Config $taxConfig
     */
    public function __construct(
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\VendorsShipping\Helper\Data $helper,
        PriceCurrencyInterface $priceCurrency,
        VendorConfig $vendorConfig,
        LoggerInterface $logger,
        \Magento\Framework\Module\Manager $moduleManage,
        \Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping $resourceQuoteShipping,
        \Magento\Tax\Model\Config $taxConfig
    ) {
        $this->_vendorHelper    = $vendorHelper;
        $this->helper           = $helper;
        $this->_priceCurrency   = $priceCurrency;
        $this->_vendorConfig    = $vendorConfig;
        $this->logger           = $logger;
        $this->_moduleManage    = $moduleManage;
        $this->resourceQuoteShipping = $resourceQuoteShipping;
        $this->taxConfig = $taxConfig;
    }
    
    /**
     * Add multiple vendor order row for each vendor.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* Do nothing if the extension is not enabled.*/
        if (!$this->_vendorHelper->moduleEnabled() || !$this->helper->isEnabled()) {
            return;
        }
        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        $vendorOrderData = $observer->getOrderData();
        $vendorId = $observer->getVendorId();
        
        if ($quote->isVirtual()) {
            return;
        }
        $shippingData = $this->resourceQuoteShipping->getShippingQuoteByVendor($vendorId, $quote->getId());

        if (!$shippingData || !isset($shippingData['entity_id'])) {
            return;
        }

        $vendorOrderData->setData('shipping_description', $shippingData['shipping_description']);
        $vendorOrderData->setData('shipping_method', $shippingData['shipping_method']);
        $vendorOrderData->setData('base_shipping_amount', $shippingData['shipping_amount']);
        $vendorOrderData->setData('shipping_amount', $this->_priceCurrency->convert($shippingData['shipping_amount'], null, $order->getData('order_currency_code')));

        if ($shippingData['discount_shipping_amount']) {
            $discountAmount = $this->_priceCurrency->convert($shippingData['discount_shipping_amount'], null, $order->getData('order_currency_code'));
            $this->_processDiscountShipping($vendorOrderData, $shippingData['discount_shipping_amount'], $discountAmount);
        }

        if ($this->_moduleManage->isEnabled("Vnecoms_VendorsTax")) {
            $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
            $itemTaxs = $object_manager->get('\Vnecoms\VendorsTax\Model\ResourceModel\Order\Tax\Item')
                ->getShipTaxItemsByOrderIdAndVendorId($order->getId(), $vendorId);
            $shippingTaxAmount = 0;
            $baseShippingTaxAmount = 0;
            foreach ($itemTaxs as $tax) {
                $shippingTaxAmount += $tax["real_amount"];
                $baseShippingTaxAmount += $tax["real_base_amount"];
            }
            $this->_processTaxShipping($order, $vendorOrderData, $baseShippingTaxAmount, $shippingTaxAmount);
        } else {
            $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
            $itemTaxs = $object_manager->get('\Magento\Sales\Model\ResourceModel\Order\Tax\Item')
                ->getTaxItemsByOrderId($order->getId());
            $shippingTaxAmount = 0;
            $baseShippingTaxAmount = 0;
            foreach ($itemTaxs as $tax) {
                if ($tax['taxable_item_type'] != "shipping") continue;
                $shippingTaxAmount += ($tax["tax_percent"] * $vendorOrderData->getData('shipping_amount')) / 100;
                $baseShippingTaxAmount += ($tax["tax_percent"] * $vendorOrderData->getData('base_shipping_amount')) / 100;
            }
            $this->_processTaxShipping($order, $vendorOrderData, $baseShippingTaxAmount, $shippingTaxAmount);
        }

        return $this;
    }

    /**
     * @param $order
     * @param $vendorOrderData
     * @param $baseShippingTaxAmount
     * @param $shippingTaxAmount
     */
    private function _processTaxShipping ($order, $vendorOrderData, $baseShippingTaxAmount, $shippingTaxAmount) {
        $vendorOrderData->setData('shipping_tax_amount', $shippingTaxAmount);
        $vendorOrderData->setData('base_shipping_tax_amount', $baseShippingTaxAmount);

        $shippingPriceIncludesTax = $this->taxConfig->shippingPriceIncludesTax($order->getStore());

        if (!$shippingPriceIncludesTax) {
            $vendorOrderData->setData('shipping_incl_tax', $vendorOrderData->getData("shipping_amount") + $shippingTaxAmount);
            $vendorOrderData->setData('base_shipping_incl_tax', $vendorOrderData->getData("base_shipping_amount") + $baseShippingTaxAmount);
        } else {
            $vendorOrderData->setData('shipping_incl_tax', $vendorOrderData->getData("shipping_amount"));
            $vendorOrderData->setData('base_shipping_incl_tax', $vendorOrderData->getData("base_shipping_amount"));
            $vendorOrderData->setData('shipping_amount', $vendorOrderData->getData("shipping_amount") - $shippingTaxAmount);
            $vendorOrderData->setData('base_shipping_amount', $vendorOrderData->getData("base_shipping_amount") - $baseShippingTaxAmount);
        }
        $vendorOrderData->setData('tax_amount', $vendorOrderData->getData("tax_amount") + $shippingTaxAmount);
        $vendorOrderData->setData('base_tax_amount', $vendorOrderData->getData("base_tax_amount") + $baseShippingTaxAmount);
    }

    /**
     * @param $vendorOrder
     * @param $baseDiscountAmount
     * @param $discountAmount
     */
    private function _processDiscountShipping($vendorOrder, $baseDiscountAmount, $discountAmount) {
        $vendorOrder->setShippingDiscountAmount($discountAmount);
        $vendorOrder->setBaseShippingDiscountAmount($baseDiscountAmount);

        $vendorOrder->setDiscountAmount($vendorOrder->getDiscountAmount() + $vendorOrder->getShippingDiscountAmount());
        $vendorOrder->setBaseDiscountAmount($vendorOrder->getBaseDiscountAmount() + $vendorOrder->getBaseShippingDiscountAmount());
    }
}
