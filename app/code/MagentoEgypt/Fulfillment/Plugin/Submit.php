<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Plugin;

final class Submit
{
    public function __construct(
        private \MagentoEgypt\Fulfillment\Model\Configuration $config,
        private \MagentoEgypt\Fulfillment\Model\QuotePlan $plans,
        private \Magento\Framework\Lock\LockManagerInterface $locks
    ) {}

    public function aroundSubmit($subject, callable $proceed, $quote, $orderData=[])
    {
        if (!$this->config->checkoutEnabled() || $quote->isVirtual()) return $proceed($quote,$orderData);
        if ($quote->getIsMultiShipping()) throw new \Magento\Framework\Exception\LocalizedException(__('Use one delivery address for Hub fulfillment.'));
        // Serializes allocation through order persistence. MSI remains responsible for stock-level reservations.
        $key='hub_fulfillment_allocate';
        if (!$this->locks->lock($key,10)) throw new \Magento\Framework\Exception\LocalizedException(__('Delivery allocation is busy. Please retry.'));
        try {
            $address=$quote->getShippingAddress();
            $method=(string)$address->getShippingMethod();
            if (!in_array($method,['hubfulfillment_direct','hubfulfillment_hub'],true)) throw new \DomainException('Please select a Hub Market delivery method.');
            $plan=$this->plans->forAddress($quote->getAllItems(),$address,substr($method,15));
            if (($plan['status']??'')!=='proposed') throw new \DomainException('The delivery allocation is no longer available.');
            // Magento's standard tax/discount collectors operate on the carrier price. Compare the original rate, not discounted shipping.
            $rate=$address->getShippingRateByCode($method);
            if (!$rate || (int)round((float)$rate->getPrice()*100)!==$plan['shipping_minor']) throw new \DomainException('Delivery rates changed. Please refresh checkout.');
            $quote->setData('hf_pending_plan',$plan);
            return $proceed($quote,$orderData);
        } catch (\DomainException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        } finally {
            $quote->unsetData('hf_pending_plan');
            $this->locks->unlock($key);
        }
    }
}
