<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Observer;

final class ImmutablePlan implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order=$observer->getOrder();
        if ($order->getOrigData('hf_plan_json') && $order->getOrigData('hf_plan_json')!==$order->getData('hf_plan_json')) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The original delivery allocation cannot be changed. Cancel and reorder instead.'));
        }
    }
}
