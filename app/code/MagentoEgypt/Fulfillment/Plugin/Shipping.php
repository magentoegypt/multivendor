<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Plugin;

/** Replaces the complete rate collection only when the new checkout feature is enabled. */
final class Shipping
{
    public function __construct(
        private \MagentoEgypt\Fulfillment\Model\Configuration $config,
        private \MagentoEgypt\Fulfillment\Model\QuotePlan $plans,
        private \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $methods,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function aroundCollectRates($subject, callable $proceed, $request)
    {
        if (!$this->config->checkoutEnabled()) return $proceed($request);
        $subject->getResult()->reset();
        $items=$request->getAllItems()??[];
        if (!$items) return $subject;
        $first=reset($items); $address=$first->getAddress();
        if (!$address) return $subject;
        foreach (['direct'=>__('Direct delivery'),'hub'=>__('Consolidated hub delivery')] as $strategy=>$label) {
            try {
                $plan=$this->plans->forAddress($items,$address,$strategy);
                if (($plan['status']??'')!=='proposed') continue;
                $method=$this->methods->create();
                $method->setCarrier('hubfulfillment')->setCarrierTitle(__('Hub Market delivery'))
                    ->setMethod($strategy)->setMethodTitle($label)->setPrice($plan['shipping_minor']/100)
                    ->setCost(array_sum(array_column($plan['groups'],'estimated_cost_minor'))/100);
                $subject->getResult()->append($method);
            } catch (\Throwable $e) {
                $this->logger->warning('Hub fulfillment rate unavailable',['exception'=>$e]);
            }
        }
        return $subject;
    }
}
