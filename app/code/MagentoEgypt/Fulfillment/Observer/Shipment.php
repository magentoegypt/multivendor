<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Observer;

final class Shipment implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        private \MagentoEgypt\Fulfillment\Model\Allocation $allocations,
        private \MagentoEgypt\Fulfillment\Model\Journal $journal,
        private \Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface $singleSource,
        private \Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface $defaultSource,
        private \Magento\InventoryShipping\Model\GetItemsToDeductFromShipment $items
    ) {}
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shipment=$observer->getShipment(); $order=$shipment->getOrder();
        if (!$order->getData('hf_plan_json') || $shipment->getOrigData('entity_id')) return;
        $source=$shipment->getExtensionAttributes()?->getSourceCode();
        if (!$source && $this->singleSource->execute()) $source=$this->defaultSource->getCode();
        if (!$source) throw new \Magento\Framework\Exception\LocalizedException(__('Select the allocated source for this shipment.'));
        $items=[];
        foreach ($this->items->execute($shipment) as $item) $items[]=['sku'=>$item->getSku(),'qty_milli'=>(int)round($item->getQty()*1000)];
        $this->journal->record('shipment:'.$shipment->getId(),(int)$order->getId(),'source_shipped',
            ['source'=>$source,'items'=>$items],[],(string)$order->getBaseCurrencyCode(),function() use($order,$source,$items) {
                foreach ($items as $item) $this->allocations->release((int)$order->getId(),$source,$item['sku'],$item['qty_milli']);
            });
    }
}
