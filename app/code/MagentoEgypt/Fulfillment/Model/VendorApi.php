<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

final class VendorApi implements \MagentoEgypt\Fulfillment\Api\VendorInterface
{
    public function __construct(
        private \Magento\Authorization\Model\UserContextInterface $user,
        private \Magento\Framework\App\ResourceConnection $resource,
        private Configuration $configuration,
        private \Magento\Framework\App\Config\Storage\WriterInterface $writer,
        private \Magento\Framework\App\Cache\TypeListInterface $cache,
        private \Magento\Framework\Lock\LockManagerInterface $locks,
        private \Magento\Sales\Api\OrderRepositoryInterface $orders,
        private Workflow $workflow,
        private Dispatch $dispatch
    ) {}

    private function vendorId(): int
    {
        if ($this->user->getUserType()!==\Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER || !$this->user->getUserId()) throw new \Magento\Framework\Exception\AuthorizationException(__('Sign in as a vendor.'));
        $db=$this->resource->getConnection();
        $id=$db->fetchOne($db->select()->from(['v'=>$this->resource->getTableName('ves_vendor_entity')],'entity_id')
            ->joinInner(['u'=>$this->resource->getTableName('ves_vendor_user')],'u.vendor_id=v.entity_id',[])
            ->where('u.customer_id = ?', (int)$this->user->getUserId())->where('u.is_super_user = ?',1)->where('v.status = ?', 1));
        if (!$id) throw new \Magento\Framework\Exception\AuthorizationException(__('An active vendor account is required.'));
        return (int)$id;
    }

    public function workspace()
    {
        return $this->workspaceForVendor($this->vendorId());
    }

    /** Internal entry point for the authenticated vendor web controller, never a REST parameter. */
    public function workspaceForVendor(int $id): string
    {
        $p=$this->configuration->get(); $db=$this->resource->getConnection();
        $orders=$db->fetchAll($db->select()->from(['o'=>$this->resource->getTableName('sales_order')],['entity_id','increment_id','hf_plan_json'])
            ->joinInner(['i'=>$this->resource->getTableName('sales_order_item')],'i.order_id=o.entity_id',[])
            ->where('i.vendor_id = ?', $id)->where('o.hf_plan_json IS NOT NULL')->distinct()->order('o.entity_id DESC')->limit(50));
        $groups=[];
        foreach ($orders as $order) foreach (json_decode($order['hf_plan_json'],true,64,JSON_THROW_ON_ERROR)['groups'] as $g) {
            if (!in_array($id,array_column($g['items'],'vendor_id'),true)) continue;
            $g['items']=array_values(array_filter($g['items'],fn($i)=>$i['vendor_id']===$id));
            $g['state']=$this->workflow->state((int)$order['entity_id'],$g);
            $g['can_manage']=$g['mode']==='vendor';
            unset($g['estimated_cost_minor'],$g['cost_owner'],$g['rate_id']);
            // Shared hub charges are marketplace charges, never the vendor's private invoice.
            if ($g['leg']==='outbound') unset($g['shipping_minor'],$g['source']);
            $groups[]=['order_id'=>(int)$order['entity_id'],'order_number'=>$order['increment_id'],'group'=>$g];
        }
        return json_encode(['vendor_id'=>$id,'modes'=>$p['vendors'][$id]??[],
            'products'=>array_values(array_filter($p['products'],fn($v)=>$v['vendor_id']===$id)),
            'sources'=>array_values(array_filter($p['sources'],fn($s)=>$s['vendor_id']===$id)),
            'groups'=>$groups],JSON_THROW_ON_ERROR);
    }

    public function savePolicy($policyJson)
    {
        return $this->saveForVendor($this->vendorId(), $policyJson);
    }

    public function saveForVendor(int $id, $policyJson): bool
    {
        if (!is_string($policyJson) || strlen($policyJson)>20000) throw new \Magento\Framework\Exception\InputException(__('Invalid policy.'));
        $input=json_decode($policyJson,true,16,JSON_THROW_ON_ERROR);
        if (!isset($input['modes'],$input['products']) || !is_array($input['products']) || count($input['products'])>100) throw new \DomainException('Invalid vendor policy.');
        if (!$this->locks->lock('hub_fulfillment_policy',10)) throw new \DomainException('Policy is busy. Retry.');
        try {
            // Read the committed row under the shared writer lock, not a stale request-scoped cache.
            $db=$this->resource->getConnection();
            $raw=$db->fetchOne($db->select()->from($this->resource->getTableName('core_config_data'),'value')->where('scope = ?','default')->where('scope_id = ?',0)->where('path = ?','hubfulfillment/general/policy'));
            if (!$raw) $raw=$this->configuration->getJson();
            $p=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
            $p['vendors']=array_values(array_filter($p['vendors'],fn($v)=>$v['vendor_id']!==$id));
            $p['vendors'][]=['vendor_id'=>$id,'modes'=>$input['modes']];
            $p['products']=array_values(array_filter($p['products'],fn($v)=>$v['vendor_id']!==$id));
            foreach ($input['products'] as $product) {
                $owner=$db->fetchOne($db->select()->from($this->resource->getTableName('catalog_product_entity'),'vendor_id')->where('sku = ?', (string)($product['sku']??'')));
                if ((int)$owner!==$id) throw new \Magento\Framework\Exception\AuthorizationException(__('You can only configure your own products.'));
                $entry=['sku'=>$product['sku'],'vendor_id'=>$id,'modes'=>$product['modes']??[]];
                if (array_key_exists('coverage',$product)) $entry['coverage']=$product['coverage'];
                $p['products'][]=$entry;
            }
            $json=json_encode($p,JSON_THROW_ON_ERROR); $this->configuration->validate($json);
            $this->writer->save('hubfulfillment/general/policy',$json); $this->cache->cleanType('config');
            return true;
        } finally { $this->locks->unlock('hub_fulfillment_policy'); }
    }

    public function progress($orderId,$groupId,$state,$operationKey)
    {
        return $this->workflow->transition($this->orders->get((int)$orderId),(string)$groupId,(string)$state,(string)$operationKey,$this->vendorId());
    }
    public function order($orderId)
    {
        $result=$this->dispatch->describe($this->orders->get((int)$orderId),$this->vendorId());
        if (!$result['groups']) throw new \Magento\Framework\Exception\AuthorizationException(__('Order access denied.'));
        return json_encode($result,JSON_THROW_ON_ERROR);
    }
    public function dispatch($payloadJson)
    {
        $id=$this->vendorId();
        if (!is_string($payloadJson) || strlen($payloadJson)>12000) throw new \DomainException('Invalid dispatch event.');
        $input=json_decode($payloadJson,true,16,JSON_THROW_ON_ERROR);
        try { return json_encode($this->dispatch->apply($this->orders->get((int)($input['order_id']??0)),$payloadJson,$id,'customer:'.$this->user->getUserId()),JSON_THROW_ON_ERROR); }
        catch (\DomainException $e) { throw new \Magento\Framework\Webapi\Exception(__($e->getMessage()),0,str_contains($e->getMessage(),'version changed') || str_contains($e->getMessage(),'Operation key')?409:400); }
    }
}
