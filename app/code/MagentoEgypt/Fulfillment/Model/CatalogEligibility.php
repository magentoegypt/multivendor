<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

/** Request-scoped eligibility set, applied before search pagination and aggregations. */
final class CatalogEligibility
{
    private array $cache=[];
    public function __construct(private Configuration $configuration, private Planner $planner,
        private \MagentoEgypt\DeliveryAvailability\Model\Availability $availability,
        private \MagentoEgypt\DeliveryAvailability\Model\Rules $rules,
        private \Magento\Framework\App\ResourceConnection $resource,
        private \Magento\Framework\App\Config\ScopeConfigInterface $config,
        private \Magento\InventorySalesApi\Api\StockResolverInterface $stocks,
        private \Magento\Store\Model\StoreManagerInterface $stores) {}

    public function ids(string $country,int $region,int $city,int $locality): array
    {
        $store=$this->stores->getStore(); $key=implode(':',[$store->getId(),$country,$region,$city,$locality]);
        if (isset($this->cache[$key])) return $this->cache[$key];
        $location=$this->availability->location($country,$region,$city,$locality);
        $dest=['country'=>$location['country_id'],'city_id'=>(int)$location['location_id'],'locality_id'=>(int)($location['locality']['location_id']??0)];
        $policy=$this->configuration->get(); if (!$policy['sources']) return $this->cache[$key]=[];
        $db=$this->resource->getConnection(); $stock=(int)$this->stocks->execute('website',$store->getWebsite()->getCode())->getStockId();
        $statusId=(int)$db->fetchOne($db->select()->from(['a'=>$this->resource->getTableName('eav_attribute')],'attribute_id')
            ->joinInner(['t'=>$this->resource->getTableName('eav_entity_type')],'t.entity_type_id=a.entity_type_id',[])
            ->where('t.entity_type_code = ?','catalog_product')->where('a.attribute_code = ?','status'));
        $query=$db->select()->from(['p'=>$this->resource->getTableName('catalog_product_entity')],['entity_id','sku','vendor_id'])
            ->joinInner(['w'=>$this->resource->getTableName('catalog_product_website')],'w.product_id=p.entity_id',[])
            ->joinInner(['i'=>$this->resource->getTableName('inventory_stock_'.$stock)],'i.sku=p.sku',['quantity','is_salable'])
            ->joinLeft(['c'=>$this->resource->getTableName('cataloginventory_stock_item')],'c.product_id=p.entity_id AND c.stock_id=1',['min_qty','use_config_min_qty'])
            ->joinLeft(['sd'=>$this->resource->getTableName('catalog_product_entity_int')],'sd.entity_id=p.entity_id AND sd.attribute_id='.$statusId.' AND sd.store_id=0',[])
            ->joinLeft(['ss'=>$this->resource->getTableName('catalog_product_entity_int')],'ss.entity_id=p.entity_id AND ss.attribute_id='.$statusId.' AND ss.store_id='.(int)$store->getId(),[])
            ->where('COALESCE(ss.value,sd.value) = ?',1)
            ->where('w.website_id = ?',(int)$store->getWebsiteId())->where('p.type_id = ?','simple')->where('i.is_salable = ?',1);
        $products=$db->fetchAll($query);
        $sources=$db->fetchAll($db->select()->from(['i'=>$this->resource->getTableName('inventory_source_item')],['sku','source_code','quantity'])
            ->joinInner(['s'=>$this->resource->getTableName('inventory_source')],'s.source_code=i.source_code',[])
            ->joinInner(['l'=>$this->resource->getTableName('inventory_source_stock_link')],'l.source_code=i.source_code',[])
            ->where('i.source_code IN (?)',array_keys($policy['sources']))->where('i.status = ?',1)->where('s.enabled = ?',1)->where('l.stock_id = ?',$stock));
        $holds=[];
        if ($this->configuration->checkoutEnabled()) foreach ($db->fetchAll($db->select()->from($this->resource->getTableName('me_fulfillment_allocation'),
            ['sku','source_code','held'=>new \Zend_Db_Expr('SUM(qty_milli-released_milli)')])->group(['sku','source_code'])) as $h) $holds[$h['sku']][$h['source_code']]=(int)$h['held'];
        $inventory=[];
        foreach ($sources as $s) $inventory[$s['sku']][$s['source_code']]=max(0,(int)floor((float)$s['quantity']*1000)-($holds[$s['sku']][$s['source_code']]??0));
        $reserved=$db->fetchPairs($db->select()->from($this->resource->getTableName('inventory_reservation'),['sku','reserved'=>new \Zend_Db_Expr('SUM(quantity)')])->where('stock_id = ?',$stock)->group('sku'));
        $rules=$this->availability->rules(); $ids=[];
        foreach ($products as $product) {
            $sku=$product['sku'];
            $minimum=$product['use_config_min_qty'] ? (float)$this->config->getValue('cataloginventory/item_options/min_qty','store',$store->getId()) : (float)$product['min_qty'];
            if ((float)$product['quantity']+(float)($reserved[$sku]??0)-$minimum<1) continue;
            if (in_array($this->rules->evaluate($rules,$country,$city,$locality,$sku),['red','blacklist'],true)) continue;
            $line=[['sku'=>$sku,'vendor_id'=>(int)$product['vendor_id'],'qty_milli'=>1000,'weight_grams'=>0]];
            foreach (['direct','hub'] as $strategy) if ($this->planner->plan($policy,$dest,$line,$inventory,$strategy)['status']==='proposed') {
                $ids[]=(int)$product['entity_id']; break;
            }
        }
        if ($ids) {
            $parents=$db->fetchCol($db->select()->from($this->resource->getTableName('catalog_product_super_link'),'parent_id')->where('product_id IN (?)',$ids));
            $ids=array_values(array_unique(array_merge($ids,array_map('intval',$parents))));
        }
        return $this->cache[$key]=$ids;
    }
}
