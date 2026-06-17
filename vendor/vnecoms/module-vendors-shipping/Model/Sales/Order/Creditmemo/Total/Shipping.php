<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShipping\Model\Sales\Order\Creditmemo\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Order creditmemo shipping total calculation model
 */
class Shipping extends \Magento\Sales\Model\Order\Creditmemo\Total\Shipping
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Tax config
     *
     * @var \Magento\Tax\Model\Config
     */
    private $taxConfig;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /**
     * @var \Vnecoms\VendorsShipping\Helper\Data
     */
    protected $helper;


    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\VendorsShipping\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($priceCurrency, $data);
        $this->priceCurrency = $priceCurrency;
        $this->_vendorHelper    = $vendorHelper;
        $this->helper           = $helper;
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        if (!$this->_vendorHelper->moduleEnabled() || !$this->helper->isEnabled()) {
            return parent::collect($creditmemo);
        }

        $order = $creditmemo->getOrder();
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $vendorOrder = $object_manager->get('\Vnecoms\VendorsSales\Model\Order')->load($creditmemo->getVendorOrderId());

        if (!$vendorOrder->getId()) {
            return parent::collect($creditmemo);
        }

        $allowedAmount = $vendorOrder->getShippingAmount() - $vendorOrder->getShippingRefunded();
        $baseAllowedAmount = $vendorOrder->getBaseShippingAmount() - $vendorOrder->getBaseShippingRefunded();

        $allowedTaxAmount = $vendorOrder->getShippingTaxAmount() - $vendorOrder->getShippingTaxRefunded();
        $baseAllowedTaxAmount = $vendorOrder->getBaseShippingTaxAmount() - $vendorOrder->getBaseShippingTaxRefunded();

        $allowedAmountInclTax = $allowedAmount + $allowedTaxAmount;
        $baseAllowedAmountInclTax = $baseAllowedAmount + $baseAllowedTaxAmount;


        // for the credit memo
        $shippingAmount = $baseShippingAmount = $shippingInclTax = $baseShippingInclTax = 0;

        // Check if the desired shipping amount to refund was specified (from invoice or another source).
        if ($creditmemo->hasBaseShippingAmount()) {
            // For the conditional logic, we will either use amounts that always include tax -OR- never include tax.
            // The logic uses the 'base' currency to be consistent with what the user (admin) provided as input.
            $useAmountsWithTax = $this->isSuppliedShippingAmountInclTax($order);

            // Since the user (admin) supplied 'desiredAmount' it already has tax -OR- does not include tax
            $desiredAmount = $this->priceCurrency->round($creditmemo->getBaseShippingAmount());
            $maxAllowedAmount = ($useAmountsWithTax ? $baseAllowedAmountInclTax : $baseAllowedAmount);
            $originalTotalAmount = ($useAmountsWithTax ? $vendorOrder->getBaseShippingInclTax() : $vendorOrder->getBaseShippingAmount());

            // Note: ($x < $y + 0.0001) means ($x <= $y) for floats
            if ($desiredAmount < $this->priceCurrency->round($maxAllowedAmount) + 0.0001) {
                // since the admin is returning less than the allowed amount, compute the ratio being returned
                $ratio = 0;
                if ($originalTotalAmount > 0) {
                    $ratio = $desiredAmount / $originalTotalAmount;
                }
                // capture amounts without tax
                // Note: ($x > $y - 0.0001) means ($x >= $y) for floats
                if ($desiredAmount > $maxAllowedAmount - 0.0001) {
                    $shippingAmount = $allowedAmount;
                    $baseShippingAmount = $baseAllowedAmount;
                } else {
                    $shippingAmount = $this->priceCurrency->round($vendorOrder->getShippingAmount() * $ratio);
                    $baseShippingAmount = $this->priceCurrency->round($vendorOrder->getBaseShippingAmount() * $ratio);
                }
                $shippingInclTax = $this->priceCurrency->round($vendorOrder->getShippingInclTax() * $ratio);
                $baseShippingInclTax = $this->priceCurrency->round($vendorOrder->getBaseShippingInclTax() * $ratio);
            } else {
                $maxAllowedAmount = $order->getBaseCurrency()->format($maxAllowedAmount, null, false);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Maximum shipping amount allowed to refund is: %1', $maxAllowedAmount)
                );
            }
        } else {
            $shippingAmount = $allowedAmount;
            $baseShippingAmount = $baseAllowedAmount;
            $shippingInclTax = $this->priceCurrency->round($allowedAmountInclTax);
            $baseShippingInclTax = $this->priceCurrency->round($baseAllowedAmountInclTax);
        }

        $creditmemo->setShippingAmount($shippingAmount);
        $creditmemo->setBaseShippingAmount($baseShippingAmount);
        $creditmemo->setShippingInclTax($shippingInclTax);
        $creditmemo->setBaseShippingInclTax($baseShippingInclTax);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $shippingAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseShippingAmount);
        return $this;
    }

    /**
     * Returns whether the user specified a shipping amount that already includes tax
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    private function isSuppliedShippingAmountInclTax($order)
    {
        // returns true if we are only displaying shipping including tax, otherwise returns false
        return $this->getTaxConfig()->displaySalesShippingInclTax($order->getStoreId());
    }

    /**
     * Get the Tax Config.
     * In a future release, will become a constructor parameter.
     *
     * @return \Magento\Tax\Model\Config
     *
     * @deprecated
     */
    private function getTaxConfig()
    {
        if ($this->taxConfig === null) {
            $this->taxConfig = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Tax\Model\Config');
        }
        return $this->taxConfig;
    }
}
