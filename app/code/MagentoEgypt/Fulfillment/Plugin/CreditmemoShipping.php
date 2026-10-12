<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Plugin;

final class CreditmemoShipping
{
    public function __construct(private \Magento\Framework\Pricing\PriceCurrencyInterface $currency) {}

    public function aroundCollect($subject, callable $proceed, $creditmemo)
    {
        if (!$creditmemo->getOrder()->getData('hf_plan_json')) return $proceed($creditmemo);
        // Shipping belongs to the order ledger. A vendor goods refund cannot refund other groups' fees.
        if ($creditmemo->getVendorOrderId() && (float)$creditmemo->getBaseShippingAmount() > 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Refund delivery charges from the marketplace order.'));
        }
        if ($creditmemo->getVendorOrderId()) $creditmemo->setBaseShippingAmount(0);
        return (new \Magento\Sales\Model\Order\Creditmemo\Total\Shipping($this->currency))->collect($creditmemo);
    }
}
