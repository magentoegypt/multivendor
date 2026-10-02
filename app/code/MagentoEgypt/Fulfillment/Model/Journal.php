<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

final class Journal
{
    public function __construct(private \Magento\Framework\App\ResourceConnection $resource) {}

    /** Execute a business event exactly once inside the caller's transaction (or its own). */
    public function record(string $key, int $orderId, string $kind, array $payload, array $entries=[], string $currency='', ?callable $effect=null): bool
    {
        if (strlen($key)>190 || !$key || $orderId<1 || strlen($kind)>32) throw new \InvalidArgumentException('Invalid journal event.');
        $db=$this->resource->getConnection(); $events=$this->resource->getTableName('me_fulfillment_event');
        $json=json_encode($payload,JSON_THROW_ON_ERROR);
        $db->beginTransaction();
        try {
            // Lock the parent row: unique event keys alone cannot protect a check-then-apply effect.
            $exists=$db->fetchOne($db->select()->from($this->resource->getTableName('sales_order'),'entity_id')->where('entity_id = ?', $orderId)->forUpdate());
            if (!$exists) throw new \DomainException('Order does not exist.');
            $old=$db->fetchRow($db->select()->from($events)->where('event_key = ?', $key));
            if ($old) {
                if ((int)$old['order_id']!==$orderId || $old['kind']!==$kind || $old['payload_json']!==$json) throw new \DomainException('Idempotency key already belongs to different data.');
                $db->commit(); return false;
            }
            $db->insert($events,['event_key'=>$key,'order_id'=>$orderId,'kind'=>$kind,'payload_json'=>$json]);
            $id=(int)$db->lastInsertId($events);
            foreach ($entries as $entry) {
                if (!is_int($entry['amount_minor']) || $entry['amount_minor']<0 || !preg_match('/^[A-Z]{3}$/D',$currency)) throw new \InvalidArgumentException('Invalid journal amount or currency.');
                if (!$entry['amount_minor']) continue;
                $db->insert($this->resource->getTableName('me_fulfillment_journal'),[
                    'event_id'=>$id,'order_id'=>$orderId,'currency'=>$currency,
                    'debit_account'=>$entry['debit'],'credit_account'=>$entry['credit'],'amount_minor'=>$entry['amount_minor']]);
            }
            if ($effect) $effect();
            $db->commit(); return true;
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
    }
}
