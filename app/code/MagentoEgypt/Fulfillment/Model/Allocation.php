<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

/** Source holds complement core MSI reservations; they never append MSI reservations. */
final class Allocation
{
    public function __construct(private \Magento\Framework\App\ResourceConnection $resource) {}

    public function outstanding(string $sku, string $source): int
    {
        $db=$this->resource->getConnection();
        return (int)$db->fetchOne($db->select()->from($this->resource->getTableName('me_fulfillment_allocation'),
            new \Zend_Db_Expr('COALESCE(SUM(qty_milli - released_milli), 0)'))->where('sku = ?', $sku)->where('source_code = ?', $source));
    }

    public function save(int $orderId, array $plan): void
    {
        $db=$this->resource->getConnection(); $table=$this->resource->getTableName('me_fulfillment_allocation');
        foreach ($this->origins($plan) as $row) {
            $row['order_id']=$orderId;
            $old=$db->fetchRow($db->select()->from($table)->where('order_id = ?', $orderId)->where('source_code = ?', $row['source_code'])->where('sku = ?', $row['sku']));
            if ($old) {
                if ((int)$old['qty_milli'] !== $row['qty_milli']) throw new \LogicException('Order allocation is immutable.');
                continue;
            }
            $db->insert($table, $row);
        }
    }

    public function origins(array $plan): array
    {
        $rows=[]; $inbound=[];
        foreach ($plan['groups'] as $g) if ($g['leg']==='inbound') foreach ($g['items'] as $i) {
            $key=$i['sku']; $inbound[$key]=($inbound[$key]??0)+$i['qty_milli'];
        }
        foreach ($plan['groups'] as $g) foreach ($g['items'] as $i) {
            $qty=$i['qty_milli'];
            if ($g['leg']==='outbound') {
                // The outbound representation repeats inbound items; reserve their origin only once.
                $skip=min($qty,$inbound[$i['sku']]??0); $qty-=$skip; $inbound[$i['sku']]=($inbound[$i['sku']]??0)-$skip;
            }
            if (!$qty) continue;
            $key=$g['source']."\0".$i['sku'];
            $rows[$key]??=['source_code'=>$g['source'],'sku'=>$i['sku'],'qty_milli'=>0];
            $rows[$key]['qty_milli']+=$qty;
        }
        return array_values($rows);
    }

    public function release(int $orderId, string $source, string $sku, int $milli): void
    {
        $db=$this->resource->getConnection(); $table=$this->resource->getTableName('me_fulfillment_allocation');
        $row=$db->fetchRow($db->select()->from($table)->where('order_id = ?', $orderId)->where('source_code = ?', $source)->where('sku = ?', $sku)->forUpdate());
        if (!$row || $milli < 1 || $milli > (int)$row['qty_milli']-(int)$row['released_milli']) throw new \DomainException('Shipment exceeds its source allocation.');
        $db->update($table,['released_milli'=>(int)$row['released_milli']+$milli],['allocation_id = ?'=>$row['allocation_id']]);
    }

    public function cancel(int $orderId): void
    {
        $db=$this->resource->getConnection();
        $db->update($this->resource->getTableName('me_fulfillment_allocation'),['released_milli'=>new \Zend_Db_Expr('qty_milli')],['order_id = ?'=>$orderId]);
    }

    /** Release cancelled/unshipped refunded quantities, retaining shipment events as deduction authority. */
    public function reconcile(int $orderId,array $quantities): void
    {
        $db=$this->resource->getConnection(); $table=$this->resource->getTableName('me_fulfillment_allocation');
        $shipped=[];
        foreach ($db->fetchCol($db->select()->from($this->resource->getTableName('me_fulfillment_event'),'payload_json')->where('order_id = ?',$orderId)->where('kind = ?','source_shipped')) as $json) {
            $event=json_decode($json,true,64,JSON_THROW_ON_ERROR);
            foreach ($event['items']??[] as $item) $shipped[$item['sku']]=($shipped[$item['sku']]??0)+$item['qty_milli'];
        }
        foreach ($quantities as $sku=>$quantity) {
            $rows=$db->fetchAll($db->select()->from($table)->where('order_id = ?',$orderId)->where('sku = ?',$sku)->order('source_code DESC')->forUpdate());
            if (!$rows) continue;
            $target=min(array_sum(array_column($rows,'qty_milli')),$quantity['canceled']+max($quantity['refunded'],$shipped[$sku]??0));
            $release=max(0,$target-array_sum(array_column($rows,'released_milli')));
            foreach ($rows as $row) {
                $qty=min($release,(int)$row['qty_milli']-(int)$row['released_milli']);
                if ($qty) $this->release($orderId,$row['source_code'],$sku,$qty);
                $release-=$qty;
                if (!$release) break;
            }
        }
    }
}
