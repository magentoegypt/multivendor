<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Observer;

/** Internal shipping subledger. Existing Vnecoms merchandise credits/commission stay authoritative. */
final class ShippingLedger implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        private \MagentoEgypt\Fulfillment\Model\Journal $journal,
        private \MagentoEgypt\Fulfillment\Model\MoneySplit $split,
        private \Magento\Framework\App\ResourceConnection $resource
    ) {}

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $document=$observer->getInvoice() ?: $observer->getCreditmemo();
        if (!$document || !$document->getId()) return;
        $refund=(bool)$observer->getCreditmemo();
        if ($refund && (int)$document->getState()!==\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED) return;
        if (!$refund && (int)$document->getState()!==\Magento\Sales\Model\Order\Invoice::STATE_PAID) return;
        $order=$document->getOrder(); $raw=$order->getData('hf_plan_json');
        if (!$raw) return;
        $plan=json_decode($raw,true,64,JSON_THROW_ON_ERROR);
        $amount=(int)round((float)$document->getBaseShippingAmount()*100);
        // Shipping discounts are reflected in the order's charged net shipping pool.
        $pool=max(0,(int)round(((float)$order->getBaseShippingAmount()-(float)$order->getBaseShippingDiscountAmount())*100));
        $db=$this->resource->getConnection(); $db->beginTransaction();
        try {
            $db->fetchOne($db->select()->from($this->resource->getTableName('sales_order'),'entity_id')->where('entity_id = ?', (int)$order->getId())->forUpdate());
            $key=($refund?'shipping-refund:':'shipping-invoice:').$document->getId();
            $events=$db->fetchAll($db->select()->from($this->resource->getTableName('me_fulfillment_event'))
                ->where('order_id = ?', (int)$order->getId())->where('kind IN (?)',['shipping_invoice','shipping_refund']));
            $paid=$returned=0;
            foreach ($events as $e) {
                if ($e['event_key']===$key) { $db->commit(); return; }
                $data=json_decode($e['payload_json'],true,64,JSON_THROW_ON_ERROR);
                if ($e['kind']==='shipping_invoice') $paid+=$data['amount_minor']; else $returned+=$data['amount_minor'];
            }
            $amount=min($amount,$refund ? max(0,$paid-$returned) : max(0,$pool-$paid));
            $prior=$refund?$returned:$paid;
            // Cumulative allocation makes many partial documents equal one complete document.
            $weights=$this->split->weights($plan);
            $before=$this->split->allocate($prior,$weights); $after=$this->split->allocate($prior+$amount,$weights);
            $entries=[];
            foreach ($after as $owner=>$value) {
                $delta=$value-$before[$owner];
                if (!$delta) continue;
                $account=$owner==='marketplace'?'marketplace_shipping_revenue':'shipping_payable:'.substr($owner,7);
                $debit=$refund?$account:'shipping_receivable'; $credit=$refund?'customer_refund_payable':$account;
                // Largest-remainder cumulative differences can reassign a penny between owners.
                if ($delta<0) { [$debit,$credit]=[$credit,$debit]; $delta=-$delta; }
                $entries[]=['debit'=>$debit,'credit'=>$credit,'amount_minor'=>$delta];
            }
            $this->journal->record($key,(int)$order->getId(),$refund?'shipping_refund':'shipping_invoice',
                ['document_id'=>(int)$document->getId(),'amount_minor'=>$amount],$entries,(string)$order->getBaseCurrencyCode());
            $db->commit();
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
    }
}
