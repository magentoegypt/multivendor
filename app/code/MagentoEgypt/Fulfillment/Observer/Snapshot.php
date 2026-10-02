<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Observer;

final class Snapshot implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $plan=$observer->getQuote()->getData('hf_pending_plan');
        if (!$plan) return;
        $plan['reservation']='source_allocated';
        $observer->getOrder()->setData('hf_plan_json',json_encode($plan,JSON_THROW_ON_ERROR));
        $observer->getOrder()->setData('hf_validated_checkout',true);
    }
}
