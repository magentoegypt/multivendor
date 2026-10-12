<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

/** Read-only staff presentation. All monetary values are major units in base currency. */
final class FleetOrderDetails
{
    public function __construct(private \Magento\Framework\App\ResourceConnection $resource) {}

    public function enrich($order, array $result): array
    {
        $money=static fn($value)=>$value===null?null:round((float)$value,2);
        $data=static fn(string $key)=>$order->getData($key);
        $result['created_at']=$data('created_at');
        $result['order_status']=$data('status');
        $name=trim((string)$data('customer_firstname').' '.(string)$data('customer_lastname'));
        $result['customer']=['name'=>$name!==''?$name:($result['destination']['recipient']??null)];
        $result['financials']=['currency'=>$result['currency'],'grand_total'=>$money($data('base_grand_total')),
            'paid'=>$money($data('base_total_paid')),'due'=>$money($data('base_total_due')),
            'shipping_fee'=>$money($data('base_shipping_amount'))];
        $method=$order->getPayment()?->getMethod();$paid=(float)$data('base_total_paid');$total=(float)$data('base_grand_total');
        $result['payment']=['method'=>$method,'is_cod'=>$method==='cashondelivery'?true:null,
            'status'=>in_array($result['order_state'],['canceled','closed'],true)?$result['order_state']:($paid>0&&$paid>=$total?'paid':($paid>0?'partially_paid':'unpaid'))];
        $items=[];foreach($order->getAllItems() as $item){if($item->getParentItemId()||$item->getIsVirtual())continue;
            $items[(int)$item->getVendorId().'|'.$item->getSku()][]=$item;}
        $ids=[];foreach($result['groups'] as $group)foreach($group['vendor_ids'] as $id)$ids[]=(int)$id;
        $db=$this->resource->getConnection();$names=[];
        if($ids){foreach($db->fetchAll($db->select()->from($this->resource->getTableName('ves_vendor_entity'),['entity_id','company'])->where('entity_id IN (?)',array_unique($ids))) as $vendor){$names[(int)$vendor['entity_id']]=trim((string)$vendor['company'])?:null;}}
        $history=[];
        foreach($db->fetchAll($db->select()->from($this->resource->getTableName('me_fulfillment_event'),['created_at','payload_json'])->where('order_id = ?',(int)$order->getId())->where('kind = ?','dispatch_update')->order('event_id ASC')) as $row){
            $event=json_decode($row['payload_json'],true,64,JSON_THROW_ON_ERROR);$snapshot=$event['snapshot']??[];$key=$snapshot['group_id']??null;
            if(!$key)continue;$vendorId=$event['request']['actor_vendor_id']??null;
            $history[$key][]=['at'=>$row['created_at'],'action'=>$event['request']['input']['action']??$snapshot['state']??'updated',
                'actor'=>$vendorId===null?'Marketplace team':($names[$vendorId]??'Vendor #'.$vendorId),'note'=>$snapshot['note']??null];
        }
        foreach($result['groups'] as &$group){
            $group['vendors']=array_map(static fn($id)=>['id'=>(int)$id,'name'=>$names[(int)$id]??null],$group['vendor_ids']);
            $sum=0;$known=true;
            foreach($group['items'] as &$entry){
                $matches=$items[(int)$entry['vendor_id'].'|'.$entry['sku']]??[];
                $entry['name']=null;$entry['unit_price']=null;$entry['row_total']=null;$entry['image_url']=null;
                // Ambiguous duplicate lines and partial quantities are never assigned guessed values.
                if(count($matches)===1){$line=$matches[0];$entry['name']=$line->getName();$entry['unit_price']=$money($line->getBasePrice());
                    if((int)round((float)$line->getQtyOrdered()*1000)===$entry['qty_milli'])$entry['row_total']=$money($line->getBaseRowTotal());}
                if($entry['row_total']===null)$known=false;else $sum+=$entry['row_total'];
            }unset($entry);
            $group['product_value']=$known?round($sum,2):null;
            $group['value_basis']='Product subtotal before tax and discounts; transport legs may repeat products.';
            $group['cod_due']=null;$group['shipping_fee']=null;$group['shipping_fee_recipient']=null;
            $group['driver']=null;$group['vehicle']=null;$group['expected_delivery']=$group['expected_delivery']??null;$group['packed_weight_kg']=null;
            $group['pickup']=null;
            if(!empty($group['source'])){$source=$db->fetchRow($db->select()->from($this->resource->getTableName('inventory_source'),['name','street','city','region','postcode','country_id','phone'])->where('source_code = ?',$group['source']));
                if($source)$group['pickup']=['name'=>$source['name'],'address'=>array_values(array_filter([$source['street'],$source['city'],$source['region'],$source['postcode'],$source['country_id']])),'telephone'=>$source['phone']];}
            $group['history']=$history[$group['group_id']]??[];
        }unset($group);
        return $result;
    }
}
