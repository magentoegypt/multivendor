<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShipping\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class SalesQuoteSaveAfterObserver
 */
class SalesQuoteSaveAfterObserver implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsShipping\Helper\Data
     */
    protected $helper;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /**
     * @var \Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping
     */
    protected $resourceQuoteShipping;

    /**
     * SalesQuoteSaveAfterObserver constructor.
     * @param \Vnecoms\VendorsShipping\Helper\Data $helper
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping $resourceQuoteShipping
     */
    public function __construct(
        \Vnecoms\VendorsShipping\Helper\Data $helper,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping $resourceQuoteShipping
    )
    {
        $this->_vendorHelper = $vendorHelper;
        $this->helper = $helper;
        $this->resourceQuoteShipping = $resourceQuoteShipping;
    }

    /**
     * Assign quote to session
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_vendorHelper->moduleEnabled() || !$this->helper->isEnabled()) {
            return;
        }
        /* @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        $shippingMethod = str_replace('vendor_multirate_', '', $shippingMethod);
        $shippingMethods = explode(
            \Vnecoms\VendorsShipping\Plugin\Shipping::METHOD_SEPARATOR,
            $shippingMethod
        );

        $vendorShippingMethod = [];
        foreach ($shippingMethods as $method) {
            $tmpMethods = explode(
                \Vnecoms\VendorsShipping\Plugin\Shipping::SEPARATOR,
                $method
            );

            if (sizeof($tmpMethods) != 2) {
                continue;
            }
            $vendorShippingMethod[$tmpMethods[1]] = $method;
            $shippingRate = $quote->getShippingAddress()->getShippingRateByCode($method);

            if (!$shippingRate || !$shippingRate->getId()) {
                continue;
            }

            $des =  $shippingRate->getCarrierTitle().' - '.$shippingRate->getMethodTitle();
            $amount = $shippingRate->getPrice();
            $method = $shippingRate->getCode();
            $this->resourceQuoteShipping->saveVendorShippingQuote(
                $tmpMethods[1],
                $quote->getId(),
                $amount,
                $method,
                $des
            );
        }
    }
}
