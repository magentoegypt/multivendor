<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

final class Finance
{
    public function __construct(private Journal $journal, private \Magento\Framework\App\ResourceConnection $resource) {}
    /** Reconcile an actual bank/COD remittance; recording an invoice alone never proves cash receipt. */
    public function receipt($order,int $amount,string $reference,int $adminId): bool
    {
        (new Policy())->integer($amount,1,100000000);
        if (!preg_match('/^[A-Za-z0-9._-]{4,80}$/D',$reference)) throw new \DomainException('A unique bank or COD remittance reference is required.');
        $db=$this->resource->getConnection(); $db->beginTransaction();
        try {
            $db->fetchOne($db->select()->from($this->resource->getTableName('sales_order'),'entity_id')->where('entity_id = ?',(int)$order->getId())->forUpdate());
            $key='shipping-receipt:'.$reference;
            $old=$db->fetchOne($db->select()->from($this->resource->getTableName('me_fulfillment_event'),'event_id')->where('event_key = ?',$key));
            if (!$old && $amount>-$this->balance((int)$order->getId(),'shipping_receivable')) throw new \DomainException('Receipt exceeds the outstanding invoiced shipping amount.');
            $result=$this->journal->record($key,(int)$order->getId(),'shipping_receipt',
                ['amount_minor'=>$amount,'reference'=>$reference,'admin_id'=>$adminId],
                [['debit'=>'bank_clearing','credit'=>'shipping_receivable','amount_minor'=>$amount]],(string)$order->getBaseCurrencyCode());
            $db->commit(); return $result;
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
    }
    public function cost($order, string $groupId, int $amount, string $reference, int $adminId): bool
    {
        (new Policy())->integer($amount,1,100000000);
        if (!preg_match('/^[A-Za-z0-9._-]{4,80}$/D',$reference)) throw new \DomainException('A unique carrier bill reference is required.');
        $plan=json_decode((string)$order->getData('hf_plan_json'),true,64,JSON_THROW_ON_ERROR); $group=null;
        foreach ($plan['groups'] as $g) if ($g['id']===$groupId) $group=$g;
        if (!$group) throw new \DomainException('Unknown fulfillment group.');
        $ids=array_unique(array_column($group['items'],'vendor_id'));
        if ($group['cost_owner']==='vendor' && count($ids)!==1) throw new \DomainException('Ambiguous cost owner.');
        $debit=$group['cost_owner']==='vendor'?'shipping_payable:'.reset($ids):'marketplace_shipping_expense';
        return $this->journal->record('carrier-bill:'.$reference,(int)$order->getId(),'actual_carrier_cost',
            ['group_id'=>$groupId,'amount_minor'=>$amount,'reference'=>$reference,'admin_id'=>$adminId],
            [['debit'=>$debit,'credit'=>'carrier_payable','amount_minor'=>$amount]],(string)$order->getBaseCurrencyCode());
    }

    public function payout($order, int $vendorId, int $amount, string $reference, int $adminId): bool
    {
        (new Policy())->integer($amount,1,100000000);
        if ($vendorId<1 || !preg_match('/^[A-Za-z0-9._-]{4,80}$/D',$reference)) throw new \DomainException('Vendor and bank reference are required.');
        $db=$this->resource->getConnection(); $db->beginTransaction();
        try {
            $db->fetchOne($db->select()->from($this->resource->getTableName('sales_order'),'entity_id')->where('entity_id = ?', (int)$order->getId())->forUpdate());
            $account='shipping_payable:'.$vendorId; $key='shipping-payout:'.$reference;
            $old=$db->fetchOne($db->select()->from($this->resource->getTableName('me_fulfillment_event'),'event_id')->where('event_key = ?',$key));
            if (!$old && $this->balance((int)$order->getId(),'shipping_receivable')<0) throw new \DomainException('Reconcile the collected shipping funds before recording a payout.');
            if (!$old && $this->balance((int)$order->getId(),$account)<$amount) throw new \DomainException('Payout exceeds the available shipping payable.');
            $result=$this->journal->record($key,(int)$order->getId(),'shipping_payout',
                ['vendor_id'=>$vendorId,'amount_minor'=>$amount,'reference'=>$reference,'admin_id'=>$adminId],
                [['debit'=>$account,'credit'=>'bank_clearing','amount_minor'=>$amount]],(string)$order->getBaseCurrencyCode());
            $db->commit(); return $result;
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
    }

    public function balance(int $orderId, string $account): int
    {
        $db=$this->resource->getConnection(); $table=$this->resource->getTableName('me_fulfillment_journal');
        $credit=(int)$db->fetchOne($db->select()->from($table,new \Zend_Db_Expr('COALESCE(SUM(amount_minor),0)'))->where('order_id = ?',$orderId)->where('credit_account = ?',$account));
        $debit=(int)$db->fetchOne($db->select()->from($table,new \Zend_Db_Expr('COALESCE(SUM(amount_minor),0)'))->where('order_id = ?',$orderId)->where('debit_account = ?',$account));
        return $credit-$debit;
    }
}
