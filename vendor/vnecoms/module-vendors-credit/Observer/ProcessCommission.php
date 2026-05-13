<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCredit\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProcessCommission implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Vnecoms\VendorsCredit\Helper\Commisssion
     */
    protected $commisssion;

    /**
     * ProcessCommission constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Vnecoms\VendorsCredit\Helper\Commisssion $commisssion
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->commisssion = $commisssion;
    }

    /**
     * Add multiple vendor order row for each vendor.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Vnecoms\VendorsSales\Model\Order\Invoice */
        $vendorInvoice = $observer->getVendorInvoice();
        /** @var \Vnecoms\VendorsSales\Model\Order */
        $vendorOrder = $vendorInvoice->getOrder();
        /** @var \Magento\Sales\Model\Order */
        $order = $vendorOrder->getOrder();

        /*Ignore commission calculation for individual payment method*/
        $paymentMethod = $order->getPayment()->getMethod();
        $flag = $this->_scopeConfig->getValue('payment/'.$paymentMethod.'/ignore_commission_calculation');
        if ($flag) {
            return;
        }
        $ignoreEscrow = $observer->getIgnoreEscrow();

        return $this->commisssion->processCommission(
            $vendorInvoice,
            $ignoreEscrow
        );
    }
}
