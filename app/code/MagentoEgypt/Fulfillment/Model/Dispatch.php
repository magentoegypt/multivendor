<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

/** Operational reporting is separate from MSI deductions and the immutable checkout price. */
final class Dispatch
{
    public function __construct(private \Magento\Framework\App\ResourceConnection $resource,
        private Journal $journal,private Workflow $workflow,private Configuration $configuration,
        private \Magento\Framework\App\Config\ScopeConfigInterface $config) {}

    public function describe($order,?int $vendorId=null): array
    {
        $raw=$order->getData('hf_plan_json'); $legacy=!$raw;
        $groups=$legacy?[]:json_decode($raw,true,64,JSON_THROW_ON_ERROR)['groups'];
        if ($legacy) {
            $unit=(string)$this->config->getValue('general/locale/weight_unit','store',$order->getStoreId());
            foreach ($order->getAllItems() as $item) {
                if ($item->getParentItemId() || $item->getIsVirtual()) continue;
                $owner=(int)$item->getVendorId();$key='legacy-vendor-'.$owner;
                $groups[$key]??=['id'=>$key,'leg'=>'direct','mode'=>null,'items'=>[]];
                $groups[$key]['items'][]=['sku'=>(string)$item->getSku(),'vendor_id'=>$owner,
                    'qty_milli'=>(int)round((float)$item->getQtyOrdered()*1000),'weight_grams'=>(float)$item->getWeight()>0?(int)ceil((float)$item->getWeight()*($unit==='lbs'?453.59237:1000)):null];
            }
            $groups=array_values($groups);
        }
        $events=$this->events((int)$order->getId());$output=[];
        foreach ($groups as $group) {
            $owners=array_values(array_unique(array_column($group['items'],'vendor_id')));
            if ($vendorId!==null && !in_array($vendorId,$owners,true)) continue;
            $saved=$events[$group['id']]??null;
            $responsibility=$legacy?($saved['responsibility']??null):($group['mode']==='vendor'?'vendor':'marketplace');
            $value=['group_id'=>$group['id'],'vendor_ids'=>$owners,'leg'=>$group['leg'],'legacy'=>$legacy,
                'responsibility'=>$responsibility,'execution'=>null,'carrier'=>null,'tracking'=>null,'note'=>null,
                'state'=>$responsibility?'planned':'unclassified','version'=>0,'updated_at'=>null];
            if ($saved) $value=array_replace($value,$saved);
            $value['source_state']=$legacy?'legacy_unallocated':$this->workflow->state((int)$order->getId(),$group);
            $value['items']=$vendorId===null?$group['items']:array_values(array_filter($group['items'],fn($i)=>$i['vendor_id']===$vendorId));
            $value['source']=$legacy?null:$group['source'];
            $value['label']=$legacy?'Existing vendor order':$group['leg'].' / '.$group['source'];
            $value['parcel_count']=null;
            $value['supported_actions']=$this->actions($value,$vendorId);
            if (in_array($order->getState(),['canceled','closed'],true)) $value['supported_actions']=[];
            $output[]=$value;
        }
        $address=$order->getShippingAddress();
        return ['contract_version'=>1,'order_id'=>(int)$order->getId(),'order_number'=>(string)$order->getIncrementId(),
            'order_state'=>(string)$order->getState(),'currency'=>(string)$order->getBaseCurrencyCode(),
            'destination'=>$address?['country'=>(string)$address->getCountryId(),'region_id'=>(int)$address->getRegionId(),
                'region'=>(string)$address->getRegion(),'city_id'=>(int)$address->getData('cm_city_id'),
                'city'=>(string)$address->getCity(),'locality_id'=>(int)$address->getData('cm_locality_id'),
                'street'=>$address->getStreet(),'postcode'=>(string)$address->getPostcode(),
                'recipient'=>trim($address->getFirstname().' '.$address->getLastname()),'telephone'=>(string)$address->getTelephone(),
                'verified_pin'=>null]:null,
            'groups'=>$output];
    }

    private function events(int $orderId): array
    {
        $db=$this->resource->getConnection();$result=[];
        if (!$db->isTableExists($this->resource->getTableName('me_fulfillment_event'))) return [];
        foreach($db->fetchCol($db->select()->from($this->resource->getTableName('me_fulfillment_event'),'payload_json')
            ->where('order_id = ?',$orderId)->where('kind = ?','dispatch_update')->order('event_id ASC')) as $raw) {
            $value=json_decode($raw,true,64,JSON_THROW_ON_ERROR); $result[$value['snapshot']['group_id']]=$value['snapshot'];
        }
        return $result;
    }

    private function actions(array $g,?int $vendorId): array
    {
        if ($g['state']==='unclassified') return ['classify'];
        if ($vendorId===null && $g['responsibility']==='vendor') return []; // Fleet monitors, never routes vendor groups.
        if ($vendorId!==null && $g['responsibility']==='marketplace') {
            return $g['leg']!=='outbound' && $g['vendor_ids']===[$vendorId] && $g['state']==='planned'?['ready']:[];
        }
        return match($g['state']) {
            'planned','ready'=>['ship','issue'],
            'dispatched','shipped_reported'=>[$g['leg']==='inbound'?'received':'delivered','issue'],
            'issue'=>['resume'],
            default=>[],
        };
    }

    public function apply($order,string $json,?int $vendorId=null,string $actor='internal'): array
    {
        if (strlen($json)>12000) throw new \DomainException('Dispatch payload is too large.');
        $input=json_decode($json,true,16,JSON_THROW_ON_ERROR);
        if (!is_array($input) || !is_int($input['order_id']??null) || $input['order_id']!==(int)$order->getId()
            || !is_string($input['group_id']??null) || !is_string($input['action']??null)
            || !is_int($input['expected_version']??null) || $input['expected_version']<0
            || !is_string($input['operation_key']??null) || !preg_match('/^[A-Za-z0-9._-]{8,100}$/D',$input['operation_key'])) throw new \DomainException('Invalid dispatch event.');
        foreach (['execution','carrier','tracking','note','responsibility'] as $field) if (isset($input[$field]) && (!is_string($input[$field]) || strlen($input[$field])>500 || preg_match('/[\x00-\x1f]/',$input[$field]))) throw new \DomainException('Invalid dispatch text.');
        ksort($input);$request=['input'=>$input,'actor_vendor_id'=>$vendorId,'actor'=>$actor];
        $db=$this->resource->getConnection();$db->beginTransaction();
        try {
            $persistedState=$db->fetchOne($db->select()->from($this->resource->getTableName('sales_order'),'state')->where('entity_id = ?',(int)$order->getId())->forUpdate());
            $key='dispatch:'.$order->getId().':'.$input['operation_key'];
            $old=$db->fetchOne($db->select()->from($this->resource->getTableName('me_fulfillment_event'),'payload_json')->where('event_key = ?',$key));
            if ($old) {
                $old=json_decode($old,true,64,JSON_THROW_ON_ERROR);
                if ($old['request']!==$request) throw new \DomainException('Operation key belongs to another event.');
                $db->commit();return ['applied'=>false,'group'=>$old['snapshot']];
            }
            if (in_array($persistedState,['canceled','closed'],true) || in_array($order->getState(),['canceled','closed'],true)) throw new \DomainException('This order cannot be dispatched.');
            $group=null;foreach($this->describe($order,$vendorId)['groups'] as $candidate) if($candidate['group_id']===$input['group_id'])$group=$candidate;
            if (!$group) throw new \Magento\Framework\Exception\AuthorizationException(__('Group access denied.'));
            if ($group['version']!==$input['expected_version']) throw new \DomainException('Dispatch version changed. Refresh before retrying.');
            if (!in_array($input['action'],$group['supported_actions'],true)) throw new \DomainException('Action is not available for this group and role.');
            switch($input['action']) {
                case 'classify':
                    $owner=$input['responsibility']??'';
                    if (!in_array($owner,['vendor','marketplace'],true) || ($owner==='vendor' && (count($group['vendor_ids'])!==1 || $group['vendor_ids'][0]<1))) throw new \DomainException('Choose a valid delivery responsibility.');
                    try {$policy=$this->configuration->get();} catch (\RuntimeException $e) {throw new \DomainException('Configure the fulfillment policy before selecting delivery responsibility.');}
                    foreach ($group['items'] as $item) {
                        $override=$policy['products'][$item['sku']]??null;
                        if ($override && $override['vendor_id']!==$item['vendor_id']) throw new \DomainException('Product ownership differs from the fulfillment policy.');
                        $modes=$override['modes']??$policy['vendors'][$item['vendor_id']]??[];
                        if (($owner==='vendor' && !in_array('vendor',$modes,true)) || ($owner==='marketplace' && !array_intersect(['marketplace','hub'],$modes))) throw new \DomainException('Delivery responsibility is not allowed by the product and vendor fulfillment policy.');
                    }
                    $group['responsibility']=$owner;$group['state']='planned';break;
                case 'ready': $group['state']='ready';break;
                case 'ship':
                    if (!in_array($input['execution']??null,['own','third_party'],true)) throw new \DomainException('Choose own delivery or third-party courier.');
                    if ($input['execution']==='third_party' && (trim($input['carrier']??'')==='' || trim($input['tracking']??'')==='')) throw new \DomainException('Carrier and tracking number are required.');
                    if (!$group['legacy'] && $vendorId===null && $group['source_state']==='planned') {
                        $this->workflow->transition($order,$group['group_id'],'dispatched','fleet-'.substr(hash('sha256',$key),0,40));
                    }
                    $group['execution']=$input['execution'];$group['carrier']=$input['execution']==='third_party'?trim($input['carrier']):null;
                    $group['tracking']=$input['execution']==='third_party'?trim($input['tracking']):null;
                    $group['state']=$vendorId!==null || $group['legacy']?'shipped_reported':'dispatched';break;
                case 'received': case 'delivered':
                    if (!$group['legacy'] && $vendorId===null) $this->workflow->transition($order,$group['group_id'],$input['action'],'fleet-'.substr(hash('sha256',$key),0,40));
                    $group['state']=$vendorId!==null || $group['legacy']?$input['action'].'_reported':$input['action'];break;
                case 'issue':
                    if(trim($input['note']??'')==='')throw new \DomainException('Describe the delivery issue.');
                    $group['previous_state']=$group['state'];$group['state']='issue';$group['note']=trim($input['note']);break;
                case 'resume': $group['state']=$group['previous_state']??'planned';unset($group['previous_state']);break;
            }
            $group['version']++;$group['updated_at']=gmdate('c');
            unset($group['items'],$group['supported_actions'],$group['source_state']);
            $this->journal->record($key,(int)$order->getId(),'dispatch_update',['request'=>$request,'snapshot'=>$group]);
            $db->commit();return ['applied'=>true,'group'=>$group];
        } catch(\Throwable $e) {$db->rollBack();throw $e;}
    }
}
