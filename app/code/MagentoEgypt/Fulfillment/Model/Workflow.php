<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

final class Workflow
{
    public function __construct(private Journal $journal, private \Magento\Framework\App\ResourceConnection $resource) {}

    public function state(int $orderId, array $group): string
    {
        $db=$this->resource->getConnection();
        $rows=$db->fetchCol($db->select()->from($this->resource->getTableName('me_fulfillment_event'),'payload_json')
            ->where('order_id = ?', $orderId)->where('kind = ?', 'group_progress')->order('event_id DESC'));
        foreach ($rows as $raw) { $p=json_decode($raw,true,64,JSON_THROW_ON_ERROR); if ($p['group_id']===$group['id']) return $p['state']; }
        return 'planned';
    }

    public function transition($order, string $groupId, string $next, string $key, ?int $vendorId=null): bool
    {
        if (!preg_match('/^[A-Za-z0-9-]{8,80}$/D',$key)) throw new \DomainException('Provide a unique operation key.');
        if ($order->getState()==='canceled') throw new \DomainException('Canceled orders cannot progress.');
        $plan=json_decode((string)$order->getData('hf_plan_json'),true,64,JSON_THROW_ON_ERROR);
        $group=null; foreach ($plan['groups'] as $g) if ($g['id']===$groupId) $group=$g;
        if (!$group) throw new \DomainException('Unknown fulfillment group.');
        if ($vendorId!==null && ($group['mode']!=='vendor' || array_unique(array_column($group['items'],'vendor_id'))!==[$vendorId])) {
            throw new \Magento\Framework\Exception\AuthorizationException(__('This fulfillment group is managed by the marketplace.'));
        }
        $db=$this->resource->getConnection(); $db->beginTransaction();
        try {
            $db->fetchOne($db->select()->from($this->resource->getTableName('sales_order'),'entity_id')->where('entity_id = ?', (int)$order->getId())->forUpdate());
            $payload=['group_id'=>$groupId,'state'=>$next,'actor_vendor_id'=>$vendorId];
            $eventKey='group:'.$order->getId().':'.$key;
            $old=$db->fetchOne($db->select()->from($this->resource->getTableName('me_fulfillment_event'),'payload_json')->where('event_key = ?', $eventKey));
            if ($old) {
                if ($old!==json_encode($payload,JSON_THROW_ON_ERROR)) throw new \DomainException('Operation key was reused with different data.');
                $db->commit(); return false;
            }
            $current=$this->state((int)$order->getId(),$group);
            $end=$group['leg']==='inbound'?'received':'delivered';
            if (!(($current==='planned' && $next==='dispatched') || ($current==='dispatched' && $next===$end))) throw new \DomainException('Invalid fulfillment transition.');
            if ($next==='dispatched') $this->requireShipment((int)$order->getId(),$group,$plan);
            if ($group['leg']==='outbound' && $next==='dispatched') foreach ($plan['groups'] as $inbound) {
                if ($inbound['leg']==='inbound' && $this->state((int)$order->getId(),$inbound)!=='received') throw new \DomainException('All inbound groups must be received before consolidated dispatch.');
            }
            $this->journal->record($eventKey,(int)$order->getId(),'group_progress',$payload);
            $db->commit(); return true;
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
    }

    private function requireShipment(int $orderId,array $group,array $plan): void
    {
        $db=$this->resource->getConnection(); $shipped=[];
        foreach ($db->fetchCol($db->select()->from($this->resource->getTableName('me_fulfillment_event'),'payload_json')
            ->where('order_id = ?',$orderId)->where('kind = ?','source_shipped')) as $raw) {
            $event=json_decode($raw,true,64,JSON_THROW_ON_ERROR);
            foreach ($event['items']??[] as $item) {
                $key=$event['source']."\0".$item['sku']; $shipped[$key]=($shipped[$key]??0)+$item['qty_milli'];
            }
        }
        // Hub dispatch requires origin shipments only. The outbound leg must never deduct stock a second time.
        $required=$group['leg']==='outbound' ? (new Allocation($this->resource))->origins($plan) :
            array_map(fn($i)=>['source_code'=>$group['source'],'sku'=>$i['sku'],'qty_milli'=>$i['qty_milli']],$group['items']);
        foreach ($required as $item) if (($shipped[$item['source_code']."\0".$item['sku']]??0)<$item['qty_milli']) {
            throw new \DomainException('Create the allocated Magento source shipment before confirming dispatch.');
        }
    }
}
