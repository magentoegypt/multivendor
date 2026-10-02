<?php
declare(strict_types=1);
// Read-only Magento service wiring and schema compatibility test. No configuration, inventory or order writes.
require '/var/www/multi.magento2.click/app/bootstrap.php';
spl_autoload_register(function(string $class): void {
    $prefix = 'MagentoEgypt\\Fulfillment\\';
    if (str_starts_with($class, $prefix)) {
        $path = dirname(__DIR__) . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) require $path;
    }
});
$om = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(Magento\Framework\App\State::class)->setAreaCode('frontend');
$store = $om->get(Magento\Store\Model\StoreManagerInterface::class)->getStore('en');
$om->get(Magento\Store\Model\StoreManagerInterface::class)->setCurrentStore($store);
$results = [];
$assert = function(string $case, callable $test) use (&$results): void {
    try { $test(); $results[] = ['case'=>$case,'status'=>'PASS']; }
    catch (Throwable $e) { $results[] = ['case'=>$case,'status'=>'FAIL','error'=>$e->getMessage()]; }
};
$resource = $om->get(Magento\Framework\App\ResourceConnection::class);
$db = $resource->getConnection();
$assert('Existing MSI and vendor ownership schema', function() use($db,$resource): void {
    foreach (['inventory_source','inventory_source_stock_link','inventory_source_item','ves_vendor_entity','me_city_location'] as $table) {
        if (!$db->isTableExists($resource->getTableName($table))) throw new RuntimeException('Missing table '.$table);
    }
    if (!isset($db->describeTable($resource->getTableName('catalog_product_entity'))['vendor_id'])) throw new RuntimeException('Missing product owner');
});
$assert('Preview dependencies resolve on installed Magento', function() use($om): void {
    $preview=$om->create(MagentoEgypt\Fulfillment\Model\Preview::class);
    $r=$preview->execute([], 'EG', 0, 0, 0, 'direct');
    if ($r !== ['status'=>'disabled','reservation'=>'not_reserved']) throw new RuntimeException('Preview is not disabled by default');
});
$assert('Admin backend model dependency injection', function() use($om): void { $om->create(MagentoEgypt\Fulfillment\Model\PolicyConfig::class); });
$assert('Public controller dependency injection', function() use($om): void { $om->create(MagentoEgypt\Fulfillment\Controller\Quote\Index::class); });
$assert('Empty policy validates against database without writes', function() use($om,$store): void {
    $om->create(MagentoEgypt\Fulfillment\Model\Configuration::class)->validate(json_encode([
        'version'=>1,'currency'=>$store->getBaseCurrencyCode(),'minor_digits'=>2,'sources'=>[],'vendors'=>[],'products'=>[],'rates'=>[]]));
});
$assert('Configured preview reads real product ownership and MSI stock without mutations', function() use($om,$store,$db,$resource): void {
    $cities=$om->get(MagentoEgypt\CityManager\Model\Directory::class)->options('EG','city');
    if (!$cities) throw new RuntimeException('No active Egypt city fixture');
    $city=$cities[0];
    $locality=(int)$db->fetchOne($db->select()->from($resource->getTableName('me_city_location'),'location_id')
        ->where('level = ?', 'locality')->where('is_active = ?', 1)->where('parent_id = ?', (int)$city['location_id'])->limit(1));
    $location=['country'=>'EG','city_id'=>(int)$city['location_id'],'locality_id'=>$locality];
    $collection=$om->create(Magento\Catalog\Model\ResourceModel\Product\Collection::class);
    $collection->setStoreId($store->getId())->addWebsiteFilter($store->getWebsiteId())->addAttributeToFilter('status',1)
        ->addAttributeToFilter('type_id','simple')->setPageSize(30);
    $stockId=(int)$om->get(Magento\InventorySalesApi\Api\StockResolverInterface::class)->execute('website',$store->getWebsite()->getCode())->getStockId();
    $product=null;
    foreach ($collection as $candidate) {
        if ($om->get(Magento\InventorySalesApi\Api\GetProductSalableQtyInterface::class)->execute($candidate->getSku(),$stockId) >= 1) { $product=$candidate; break; }
    }
    if (!$product) throw new RuntimeException('No positive-salable simple product available for read-only integration fixture');
    $sources=$db->fetchCol($db->select()->from(['s'=>$resource->getTableName('inventory_source')],'source_code')
        ->joinInner(['l'=>$resource->getTableName('inventory_source_stock_link')],'s.source_code = l.source_code',[])
        ->where('s.enabled = ?',1)->where('l.stock_id = ?',$stockId));
    if (!$sources) throw new RuntimeException('No enabled source for website stock');
    $p=['version'=>1,'currency'=>$store->getBaseCurrencyCode(),'minor_digits'=>2,'sources'=>[],
        'vendors'=>[['vendor_id'=>(int)$product->getData('vendor_id'),'modes'=>['marketplace']]],'products'=>[],'rates'=>[]];
    foreach ($sources as $code) {
        $p['sources'][]=['code'=>$code,'vendor_id'=>0,'kind'=>'hub','priority'=>10,'location'=>$location];
        $p['rates'][]=['id'=>'read-only-'.$code,'source'=>$code,'leg'=>'direct','mode'=>'marketplace','destination'=>$location,
            'base_minor'=>123,'per_unit_minor'=>0,'per_kg_minor'=>0,'cost_base_minor'=>100,'cost_per_unit_minor'=>0,'cost_per_kg_minor'=>0,
            'revenue_owner'=>'marketplace','cost_owner'=>'marketplace'];
    }
    $scope=new class(json_encode($p)) implements Magento\Framework\App\Config\ScopeConfigInterface {
        public function __construct(private string $policy) {}
        public function getValue($path=null,$scopeType=self::SCOPE_TYPE_DEFAULT,$scopeCode=null) { return $this->policy; }
        public function isSetFlag($path,$scopeType=self::SCOPE_TYPE_DEFAULT,$scopeCode=null) { return true; }
    };
    $configuration=$om->create(MagentoEgypt\Fulfillment\Model\Configuration::class,['config'=>$scope]);
    $preview=$om->create(MagentoEgypt\Fulfillment\Model\Preview::class,['configuration'=>$configuration]);
    $result=$preview->execute([['sku'=>$product->getSku(),'qty_milli'=>500],['sku'=>$product->getSku(),'qty_milli'=>500]],'EG',(int)$city['region_id'],(int)$city['location_id'],$locality,'direct');
    if ($result['status'] !== 'proposed' || $result['shipping_minor'] !== 123 || $result['groups'][0]['items'][0]['qty_milli'] !== 1000) {
        throw new RuntimeException('Unexpected real inventory preview result: '.json_encode($result));
    }
    foreach (['source','estimated_cost_minor','cost_owner','rate_id'] as $key) if (isset($result['groups'][0][$key])) throw new RuntimeException('Private field leaked');
    if ($result['checkout_binding'] !== 'preview_only') throw new RuntimeException('Preview must not claim checkout binding');
});
$assert('All module XML validates against Magento schemas', function(): void {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__).'/etc'));
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'xml') continue;
        $doc = new DOMDocument(); $doc->load($file->getPathname());
        $urn = $doc->documentElement->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance','noNamespaceSchemaLocation');
        $resolver = new Magento\Framework\Config\Dom\UrnResolver();
        if (!$doc->schemaValidate($resolver->getRealPath($urn))) throw new RuntimeException('Invalid XML '.$file->getFilename());
    }
});
$failed=count(array_filter($results,fn($r)=>$r['status']==='FAIL'));
echo json_encode(['suite'=>'Read-only installed Magento compatibility','passed'=>count($results)-$failed,'failed'=>$failed,'cases'=>$results],JSON_PRETTY_PRINT).PHP_EOL;
exit($failed?1:0);
