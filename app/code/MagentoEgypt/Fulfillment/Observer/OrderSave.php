<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Observer;

final class OrderSave implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(private \MagentoEgypt\Fulfillment\Model\Allocation $allocation) {}
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order=$observer->getOrder(); $json=$order->getData('hf_plan_json');
        if (!$json) return;
        $plan=json_decode($json,true,64,JSON_THROW_ON_ERROR);
        $this->allocation->save((int)$order->getId(),$plan);
        if ($order->getState()==='canceled') $this->allocation->cancel((int)$order->getId());
        else {
            $quantities=[];
            foreach ($order->getAllItems() as $item) {
                if ($item->getProductType()!=='simple' || $item->getIsVirtual()) continue;
                $sku=(string)$item->getSku();
                $quantities[$sku]??=['canceled'=>0,'refunded'=>0];
                $quantities[$sku]['canceled']+=(int)round((float)$item->getQtyCanceled()*1000);
                $quantities[$sku]['refunded']+=(int)round((float)$item->getQtyRefunded()*1000);
            }
            $this->allocation->reconcile((int)$order->getId(),$quantities);
        }
    }
}
