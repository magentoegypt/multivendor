<?php
declare(strict_types=1);
require __DIR__ . '/../Model/Policy.php';
require __DIR__ . '/../Model/Planner.php';
require __DIR__ . '/../Model/Settlement.php';
require __DIR__ . '/fixtures.php';
use MagentoEgypt\Fulfillment\Model\{Policy, Planner, Settlement};

$results = [];
function check(string $name, callable $test): void {
    global $results;
    try { $test(); $results[] = ['case'=>$name,'status'=>'PASS']; }
    catch (Throwable $e) { $results[] = ['case'=>$name,'status'=>'FAIL','error'=>$e->getMessage()]; }
}
function same(mixed $want, mixed $actual): void {
    if ($want !== $actual) throw new RuntimeException('Expected '.json_encode($want).' got '.json_encode($actual));
}
function rejects(callable $fn): void {
    try { $fn(); } catch (InvalidArgumentException | JsonException $e) { return; }
    throw new RuntimeException('Expected rejection.');
}
function parsed(?array $p = null): array { return (new Policy())->parse(json_encode($p ?? fixturePolicy(), JSON_THROW_ON_ERROR)); }
function plan(?array $p = null, ?array $lines = null, ?array $stock = null, string $strategy = 'direct', ?array $d = null): array {
    return (new Planner())->plan(parsed($p), $d ?? ['country'=>'EG','city_id'=>104,'locality_id'=>0], $lines ?? fixtureLines(), $stock ?? fixtureStock(), $strategy);
}
check('Three cities to Giza: three direct groups and summed fees', function() {
    $p=plan(); same('proposed',$p['status']); same(3,count($p['groups'])); same(6000,$p['shipping_minor']); same('not_reserved',$p['reservation']);
});
check('Three vendors: three inbound legs and one shared hub delivery', function() {
    $p=plan(strategy:'hub'); same('proposed',$p['status']); same(4,count($p['groups'])); same(2400,$p['shipping_minor']);
    same(1,count(array_filter($p['groups'],fn($g)=>$g['leg']==='outbound')));
});
check('Insufficient one seller stock invalidates the whole quote', function() {
    $p=plan(stock:['A'=>['alex'=>10000],'B'=>['aswan'=>999],'C'=>['asyut'=>10000]]);
    same('unavailable',$p['status']); same([],$p['groups']); same(null,$p['shipping_minor']);
});
check('No coverage cannot become a free shipping offer', function() {
    same('unavailable',plan(d:['country'=>'EG','city_id'=>999,'locality_id'=>0])['status']);
});
check('Vendor disabled fulfillment blocks vendor stock', function() {
    $p=fixturePolicy(); $p['vendors'][0]['modes']=[]; same('unavailable',plan($p)['status']);
});
check('Product override disables only that product', function() {
    $p=fixturePolicy(); $p['products'][]=['sku'=>'A','vendor_id'=>1,'modes'=>[]];
    same('unavailable',plan($p)['status']); same('proposed',plan($p,array_slice(fixtureLines(),1))['status']);
});
check('Product can opt into service when its vendor default is disabled', function() {
    $p=fixturePolicy(); $p['vendors'][0]['modes']=[]; $p['products'][]=['sku'=>'A','vendor_id'=>1,'modes'=>['vendor']];
    same('proposed',plan($p)['status']);
});
check('Marketplace collects directly from a seller who does not ship', function() {
    $p=fixturePolicy(); $p['vendors'][0]['modes']=['marketplace']; $p['rates'][0]['mode']='marketplace';
    $p['rates'][0]['revenue_owner']='marketplace'; $p['rates'][0]['cost_owner']='marketplace';
    $r=plan($p); same('proposed',$r['status']); same('marketplace',$r['groups'][0]['mode']);
});
check('Another vendor warehouse cannot supply the product', function() {
    same('unavailable',plan(stock:['A'=>['aswan'=>999999],'B'=>['aswan'=>10000],'C'=>['asyut'=>10000]])['status']);
});
check('Forged product policy owner fails closed', function() {
    $p=fixturePolicy(); $p['products'][]=['sku'=>'A','vendor_id'=>2,'modes'=>['vendor']]; same('owner_mismatch',plan($p)['issues'][0]['code']);
});
check('Split quantity across owned sources charges each parcel once', function() {
    $p=fixturePolicy(); $s=$p['sources'][0]; $s['code']='alex2'; $p['sources'][]=$s;
    $r=$p['rates'][0]; $r['id']='alex2-direct'; $r['source']='alex2'; $p['rates'][]=$r;
    $l=[fixtureLines()[0]]; $l[0]['qty_milli']=1500;
    $r=plan($p,$l,['A'=>['alex'=>1000,'alex2'=>1000]]); same(2,count($r['groups'])); same(2000,$r['shipping_minor']);
    same(1500,array_sum(array_map(fn($g)=>$g['items'][0]['qty_milli'],$r['groups'])));
});
check('Repeated cart SKU must be aggregated before allocation', function() { rejects(fn()=>plan(lines:[fixtureLines()[0],fixtureLines()[0]])); });
check('Existing hub stock adds no fictional inbound fee', function() {
    $r=plan(stock:['A'=>['hub-giza'=>10000],'B'=>['hub-giza'=>10000],'C'=>['hub-giza'=>10000]],strategy:'hub');
    same(1,count($r['groups'])); same(1200,$r['shipping_minor']);
});
check('Missing last-mile route blocks even stocked hubs', function() {
    $p=fixturePolicy(); array_pop($p['rates']); same('unavailable',plan($p,strategy:'hub')['status']);
});
check('Missing one inbound route blocks consolidation', function() {
    $p=fixturePolicy(); unset($p['rates'][1]); $p['rates']=array_values($p['rates']); same('unavailable',plan($p,strategy:'hub')['status']);
});
check('No configured hub blocks consolidation', function() {
    $p=fixturePolicy(); $p['sources']=array_slice($p['sources'],0,3); $p['rates']=array_values(array_filter($p['rates'],fn($r)=>$r['leg']==='direct'));
    same('no_common_hub',plan($p,strategy:'hub')['issues'][0]['code']);
});
check('Locality-specific rate overrides city and country', function() {
    $p=fixturePolicy(); $r=$p['rates'][0]; $r['destination']['city_id']=0; $r['id']='country'; $r['base_minor']=9900; $p['rates'][]=$r;
    $r=$p['rates'][0]; $r['destination']['locality_id']=500; $r['id']='local'; $r['base_minor']=300; $p['rates'][]=$r;
    same(300,plan($p,[fixtureLines()[0]],d:['country'=>'EG','city_id'=>104,'locality_id'=>500])['shipping_minor']);
    same(1000,plan($p,[fixtureLines()[0]])['shipping_minor']);
});
check('Locality configured for one city cannot leak to another city', function() {
    $p=fixturePolicy(); $p['rates'][0]['destination']['locality_id']=500;
    same('unavailable',plan($p,[fixtureLines()[0]],d:['country'=>'EG','city_id'=>999,'locality_id'=>500])['status']);
});
check('Fractional unit fee and started-kilogram rounding', function() {
    $p=fixturePolicy(); $p['rates'][0]['base_minor']=0; $p['rates'][0]['per_unit_minor']=101; $p['rates'][0]['per_kg_minor']=200;
    $l=[fixtureLines()[0]]; $l[0]['qty_milli']=500; $l[0]['weight_grams']=2500;
    same(451,plan($p,$l)['shipping_minor']);
});
check('Deterministic allocation regardless of line and policy order', function() {
    $p=fixturePolicy(); $p['sources']=array_reverse($p['sources']); $p['rates']=array_reverse($p['rates']);
    same(plan(),plan($p,array_reverse(fixtureLines())));
});
check('Vendor revenue, marketplace costs balance separately', function() {
    $p=fixturePolicy(); $p['rates'][0]['cost_owner']='marketplace';
    $s=(new Settlement())->project(plan($p),[['vendor_id'=>1,'net_goods_minor'=>10000,'commission_minor'=>1000],['vendor_id'=>2,'net_goods_minor'=>20000,'commission_minor'=>2000],['vendor_id'=>3,'net_goods_minor'=>30000,'commission_minor'=>3000]]);
    same([1=>10000,2=>18900,3=>28400],$s['vendor_payable_minor']); same(5400,$s['marketplace_contribution_minor']); same(66000,$s['customer_due_minor']);
});
check('Consolidated shipment billed once with marketplace contribution', function() {
    $s=(new Settlement())->project(plan(strategy:'hub'),[['vendor_id'=>1,'net_goods_minor'=>10000,'commission_minor'=>1000],['vendor_id'=>2,'net_goods_minor'=>20000,'commission_minor'=>2000],['vendor_id'=>3,'net_goods_minor'=>30000,'commission_minor'=>3000]]);
    same(62400,$s['customer_due_minor']); same(6700,$s['marketplace_contribution_minor']); same(1700,$s['estimated_carrier_payable_minor']); same([1=>9000,2=>18000,3=>27000],$s['vendor_payable_minor']);
});
check('Loss-making shipping stays negative rather than hiding cost', function() {
    $p=fixturePolicy(); $p['rates'][0]['base_minor']=0; $p['rates'][0]['cost_base_minor']=20000;
    $s=(new Settlement())->project(plan($p,[fixtureLines()[0]]),[['vendor_id'=>1,'net_goods_minor'=>1000,'commission_minor'=>0]]);
    same(-19000,$s['vendor_payable_minor'][1]);
});
check('Missing or unrelated seller financial data rejected', function() {
    rejects(fn()=>(new Settlement())->project(plan(),[]));
    rejects(fn()=>(new Settlement())->project(plan(lines:[fixtureLines()[0]]),[['vendor_id'=>1,'net_goods_minor'=>1000,'commission_minor'=>0],['vendor_id'=>2,'net_goods_minor'=>1,'commission_minor'=>0]]));
});
check('Commission cannot exceed net goods', function() { rejects(fn()=>(new Settlement())->project(plan(),[['vendor_id'=>1,'net_goods_minor'=>1,'commission_minor'=>2]])); });
check('Unavailable plans cannot generate settlement', function() { rejects(fn()=>(new Settlement())->project(plan(stock:[]),[])); });
foreach (['version'=>2, 'currency'=>'US', 'minor_digits'=>3] as $k=>$v) check('Reject invalid policy '.$k, function() use($k,$v) { $p=fixturePolicy(); $p[$k]=$v; rejects(fn()=>parsed($p)); });
check('Reject duplicate source', function() { $p=fixturePolicy(); $p['sources'][]=$p['sources'][0]; rejects(fn()=>parsed($p)); });
check('Reject conflicting rate destinations', function() { $p=fixturePolicy(); $r=$p['rates'][0]; $r['id']='conflict'; $p['rates'][]=$r; rejects(fn()=>parsed($p)); });
check('Reject floating point money and negative money', function() {
    foreach ([1.5,-1,'100'] as $bad) { $p=fixturePolicy(); $p['rates'][0]['base_minor']=$bad; rejects(fn()=>parsed($p)); }
});
check('Reject shared hub shipping assigned to a seller', function() { $p=fixturePolicy(); $p['rates'][6]['revenue_owner']='vendor'; rejects(fn()=>parsed($p)); });
check('Reject inbound route to a different hub city', function() { $p=fixturePolicy(); $p['rates'][1]['destination']['city_id']=999; rejects(fn()=>parsed($p)); });
check('Reject invalid quantity and unknown strategy', function() {
    foreach ([0,-1,1000001,1.5] as $bad) { $l=[fixtureLines()[0]]; $l[0]['qty_milli']=$bad; rejects(fn()=>plan(lines:$l)); }
    rejects(fn()=>plan(strategy:'cheapest_magic'));
});
check('All four countries preserve exact geography matching', function() {
    foreach (['EG','SA','AE','US'] as $country) {
        $p=fixturePolicy(); foreach ($p['sources'] as &$s) $s['location']['country']=$country; unset($s);
        foreach ($p['rates'] as &$r) $r['destination']['country']=$country; unset($r);
        same('proposed',plan($p,d:['country'=>$country,'city_id'=>104,'locality_id'=>0])['status']);
    }
});
check('Product coverage rejects direct and hub routes outside its city', function() {
    $p=fixturePolicy(); $p['products']=[['sku'=>'A','vendor_id'=>1,'modes'=>['vendor','marketplace','hub'],'coverage'=>[['country'=>'EG','city_id'=>999,'locality_id'=>0]]]];
    foreach (['direct','hub'] as $strategy) { $r=plan($p,lines:[fixtureLines()[0]],strategy:$strategy); same('unavailable',$r['status']); same('outside_product_coverage',$r['issues'][0]['code']); }
});
check('Product coverage accepts matching city and rejects a different locality', function() {
    $p=fixturePolicy(); $p['products']=[['sku'=>'A','vendor_id'=>1,'modes'=>['vendor'],'coverage'=>[['country'=>'EG','city_id'=>104,'locality_id'=>9]]]];
    same('unavailable',plan($p,lines:[fixtureLines()[0]])['status']);
    same('proposed',plan($p,lines:[fixtureLines()[0]],d:['country'=>'EG','city_id'=>104,'locality_id'=>9])['status']);
});
check('Explicit empty product coverage disables delivery; omitted coverage inherits routes', function() {
    $p=fixturePolicy(); $p['products']=[['sku'=>'A','vendor_id'=>1,'modes'=>['vendor'],'coverage'=>[]]];
    same('unavailable',plan($p,lines:[fixtureLines()[0]])['status']); unset($p['products'][0]['coverage']);
    same('proposed',plan($p,lines:[fixtureLines()[0]])['status']);
});
$failed = count(array_filter($results,fn($r)=>$r['status']==='FAIL'));
echo json_encode(['suite'=>'Hub Fulfillment deterministic contract tests','passed'=>count($results)-$failed,'failed'=>$failed,'cases'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($failed ? 1 : 0);
