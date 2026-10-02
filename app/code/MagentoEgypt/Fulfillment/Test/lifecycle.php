<?php
declare(strict_types=1);
// Dedicated synthetic schema only. Never use the production adapter for these writes.
require '/var/www/multi.magento2.click/app/bootstrap.php';
spl_autoload_register(function(string $class): void {
    $prefix='MagentoEgypt\\Fulfillment\\';
    if (str_starts_with($class,$prefix)) { $p=dirname(__DIR__).'/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if (is_file($p)) require $p; }
});
require __DIR__.'/fixtures.php';
use MagentoEgypt\Fulfillment\Model\{Allocation,Journal,MoneySplit,Workflow,Finance,Policy,Planner};
$config=json_decode(file_get_contents('/home/ubuntu/hub-fulfillment-review/qa-db.json'),true,16,JSON_THROW_ON_ERROR);
if (($config['dbname']??'')!=='hfqa_20261002_build2') throw new RuntimeException('Refusing non-QA database.');
$om=Magento\Framework\App\Bootstrap::create(BP,$_SERVER)->getObjectManager();
$om->get(Magento\Framework\App\State::class)->setAreaCode('frontend');
$db=$om->create(Magento\Framework\DB\Adapter\Pdo\Mysql::class,['config'=>$config]);
if ($db->fetchOne('SELECT DATABASE()')!==$config['dbname']) throw new RuntimeException('Wrong connection.');
$resource=new class($db) extends Magento\Framework\App\ResourceConnection {
    public function __construct(private $qa) {}
    public function getConnection($resourceName='default') { return $this->qa; }
    public function getTableName($modelEntity,$connectionName=null) { return $modelEntity; }
};
$db->query('CREATE TABLE IF NOT EXISTS sales_order (entity_id INT UNSIGNED NOT NULL PRIMARY KEY, hf_plan_json MEDIUMTEXT NULL) ENGINE=InnoDB');
if (!isset($db->describeTable('sales_order')['state'])) $db->query("ALTER TABLE sales_order ADD state VARCHAR(32) NOT NULL DEFAULT 'processing'");
$schema=simplexml_load_file(dirname(__DIR__).'/etc/db_schema.xml');
foreach ($schema->table as $table) {
    $name=(string)$table['name']; if ($name==='sales_order') continue;
    $columns=[];
    foreach ($table->column as $c) {
        $type=(string)$c->attributes('http://www.w3.org/2001/XMLSchema-instance')['type'];
        $sql=match($type) {'bigint'=>'BIGINT','int'=>'INT','varchar'=>'VARCHAR('.(int)$c['length'].')','timestamp'=>'TIMESTAMP','mediumtext'=>'MEDIUMTEXT',default=>throw new RuntimeException('Unhandled schema type')};
        $sql=$db->quoteIdentifier((string)$c['name']).' '.$sql;
        if ((string)$c['unsigned']==='true') $sql.=' UNSIGNED';
        $sql.=((string)$c['nullable']==='false')?' NOT NULL':' NULL';
        if (isset($c['default'])) $sql.=' DEFAULT '.((string)$c['default']==='CURRENT_TIMESTAMP'?'CURRENT_TIMESTAMP':$db->quote((string)$c['default']));
        if ((string)$c['identity']==='true') $sql.=' AUTO_INCREMENT';
        $columns[]=$sql;
    }
    foreach ($table->constraint as $c) {
        $type=(string)$c->attributes('http://www.w3.org/2001/XMLSchema-instance')['type'];
        $fields=[]; foreach ($c->column as $col) $fields[]=$db->quoteIdentifier((string)$col['name']);
        $columns[]=($type==='primary'?'PRIMARY KEY':'UNIQUE KEY '.$db->quoteIdentifier((string)$c['referenceId'])).' ('.implode(',',$fields).')';
    }
    $db->query('CREATE TABLE IF NOT EXISTS '.$db->quoteIdentifier($name).' ('.implode(',',$columns).') ENGINE=InnoDB');
}
// Reset only these synthetic fixture tables inside the explicitly checked private database.
foreach (['me_fulfillment_journal','me_fulfillment_event','me_fulfillment_allocation','sales_order'] as $table) $db->delete($table);
$db->insert('sales_order',['entity_id'=>1]);
$policy=(new Policy())->parse(json_encode(fixturePolicy()));
$plan=(new Planner())->plan($policy,['country'=>'EG','city_id'=>104,'locality_id'=>0],fixtureLines(),fixtureStock(),'hub');
$order=new Magento\Framework\DataObject(['id'=>1,'entity_id'=>1,'state'=>'processing','hf_plan_json'=>json_encode($plan),'base_currency_code'=>'EGP','base_shipping_amount'=>24.0,'base_shipping_discount_amount'=>0]);
$allocation=new Allocation($resource); $journal=new Journal($resource); $workflow=new Workflow($journal,$resource); $finance=new Finance($journal,$resource);
$results=[];
function same($want,$actual):void { if ($want!==$actual) throw new RuntimeException('Expected '.json_encode($want).' got '.json_encode($actual)); }
function rejects(callable $fn):void { try { $fn(); } catch (Throwable $e) { return; } throw new RuntimeException('Expected rejection'); }
function test(string $name, callable $fn):void { global $results; try {$fn();$results[]=['case'=>$name,'status'=>'PASS'];}catch(Throwable $e){$results[]=['case'=>$name,'status'=>'FAIL','error'=>$e->getMessage()];} }
test('Hub origins exclude duplicated outbound quantities',function() use($allocation,$plan){same(3,count($allocation->origins($plan)));same(3000,array_sum(array_column($allocation->origins($plan),'qty_milli')));});
test('Save source holds and replay without duplicate allocation',function() use($allocation,$plan){$allocation->save(1,$plan);$allocation->save(1,$plan);same(1000,$allocation->outstanding('A','alex'));});
test('Changed allocation cannot silently overwrite the order',function() use($allocation,$plan){$p=$plan;$p['groups'][0]['items'][0]['qty_milli']=2000;rejects(fn()=>$allocation->save(1,$p));});
test('Shipment release occurs once for an idempotent event',function() use($allocation,$journal){
    $fn=fn()=>$allocation->release(1,'alex','A',500);
    same(true,$journal->record('shipment-test',1,'source_shipped',['qty'=>500],[],'EGP',$fn));
    same(false,$journal->record('shipment-test',1,'source_shipped',['qty'=>500],[],'EGP',$fn));same(500,$allocation->outstanding('A','alex'));
});
test('Shipment cannot exceed remaining source allocation',function() use($allocation){rejects(fn()=>$allocation->release(1,'alex','A',501));});
test('Conflicting idempotency payload is rejected',function() use($journal){rejects(fn()=>$journal->record('shipment-test',1,'source_shipped',['qty'=>900]));});
test('Failed business effect rolls back event and journal',function() use($journal,$db){
    rejects(fn()=>$journal->record('rollback-test',1,'test',[],[['debit'=>'a','credit'=>'b','amount_minor'=>100]],'EGP',function(){throw new RuntimeException('Synthetic failure');}));
    same(false,$db->fetchOne("SELECT event_id FROM me_fulfillment_event WHERE event_key='rollback-test'"));
});
test('Consolidated dispatch rejected before hub receipt',function() use($workflow,$order,$plan){$g=array_values(array_filter($plan['groups'],fn($g)=>$g['leg']==='outbound'))[0];rejects(fn()=>$workflow->transition($order,$g['id'],'dispatched','op-outbound-early'));});
test('Unrelated vendor cannot update marketplace groups',function() use($workflow,$order,$plan){rejects(fn()=>$workflow->transition($order,$plan['groups'][0]['id'],'dispatched','op-foreign-user',99));});
test('Dispatch cannot claim shipment before inventory deduction evidence',function() use($workflow,$order,$plan){
    rejects(fn()=>$workflow->transition($order,$plan['groups'][0]['id'],'dispatched','op-without-shipment'));
});
test('Inbound dispatch and receipt unlock consolidated delivery',function() use($workflow,$order,$plan,$journal,$allocation){
    foreach ($allocation->origins($plan) as $n=>$origin) $journal->record('synthetic-shipment-proof-'.$n,1,'source_shipped',
        ['source'=>$origin['source_code'],'items'=>[['sku'=>$origin['sku'],'qty_milli'=>$origin['qty_milli']]]]);
    foreach ($plan['groups'] as $n=>$g) if ($g['leg']==='inbound') {$workflow->transition($order,$g['id'],'dispatched','op-inbound-dispatch-'.$n);$workflow->transition($order,$g['id'],'received','op-inbound-receipt-'.$n);}
    $g=array_values(array_filter($plan['groups'],fn($g)=>$g['leg']==='outbound'))[0];
    same(true,$workflow->transition($order,$g['id'],'dispatched','op-outbound-dispatch'));
    same(false,$workflow->transition($order,$g['id'],'dispatched','op-outbound-dispatch'));
    same(true,$workflow->transition($order,$g['id'],'delivered','op-outbound-delivered'));
    same('delivered',$workflow->state(1,$g));
});
test('Delivered groups cannot transition backwards',function() use($workflow,$order,$plan){$g=array_values(array_filter($plan['groups'],fn($g)=>$g['leg']==='outbound'))[0];rejects(fn()=>$workflow->transition($order,$g['id'],'dispatched','op-invalid-rewind'));});
test('Actual carrier cost records once with immutable reference',function() use($finance,$order,$plan){same(true,$finance->cost($order,$plan['groups'][0]['id'],350,'BILL-001',1));same(false,$finance->cost($order,$plan['groups'][0]['id'],350,'BILL-001',1));rejects(fn()=>$finance->cost($order,$plan['groups'][0]['id'],351,'BILL-001',1));same(350,$finance->balance(1,'carrier_payable'));});
test('Unpaid invoices do not accrue shipping revenue',function() use($journal,$resource,$order,$db){
    $observer=new MagentoEgypt\Fulfillment\Observer\ShippingLedger($journal,new MoneySplit(),$resource);
    $invoice=new Magento\Framework\DataObject(['id'=>10,'state'=>1,'order'=>$order,'base_shipping_amount'=>24]);
    $observer->execute(new Magento\Framework\Event\Observer(['invoice'=>$invoice]));
    same(false,$db->fetchOne("SELECT event_id FROM me_fulfillment_event WHERE event_key='shipping-invoice:10'"));
});
test('Paid invoice posts shipping once; repeat save is harmless',function() use($journal,$resource,$order,$finance){
    $observer=new MagentoEgypt\Fulfillment\Observer\ShippingLedger($journal,new MoneySplit(),$resource);
    $invoice=new Magento\Framework\DataObject(['id'=>10,'state'=>2,'order'=>$order,'base_shipping_amount'=>24]);
    $observer->execute(new Magento\Framework\Event\Observer(['invoice'=>$invoice]));$observer->execute(new Magento\Framework\Event\Observer(['invoice'=>$invoice]));
    same(2400,$finance->balance(1,'marketplace_shipping_revenue'));
});
test('Shipping refund reverses only captured amount without duplicate replay',function() use($journal,$resource,$order,$finance){
    $observer=new MagentoEgypt\Fulfillment\Observer\ShippingLedger($journal,new MoneySplit(),$resource);
    $memo=new Magento\Framework\DataObject(['id'=>20,'state'=>2,'order'=>$order,'base_shipping_amount'=>9]);
    $observer->execute(new Magento\Framework\Event\Observer(['creditmemo'=>$memo]));$observer->execute(new Magento\Framework\Event\Observer(['creditmemo'=>$memo]));
    same(1500,$finance->balance(1,'marketplace_shipping_revenue'));same(900,$finance->balance(1,'customer_refund_payable'));
});
test('Vendor payout cannot exceed shipping balance',function() use($finance,$order){rejects(fn()=>$finance->payout($order,1,1,'BANK-001',1));});
test('Completed payout records exactly once and consumes payable',function() use($journal,$finance,$order){
    $journal->record('synthetic-vendor-earned',1,'test',[],[['debit'=>'shipping_receivable','credit'=>'shipping_payable:1','amount_minor'=>1000]],'EGP');
    rejects(fn()=>$finance->payout($order,1,400,'BANK-002',1));
    rejects(fn()=>$finance->receipt($order,3401,'REMIT-001',1));
    same(true,$finance->receipt($order,3400,'REMIT-001',1));
    same(false,$finance->receipt($order,3400,'REMIT-001',1));
    same(true,$finance->payout($order,1,400,'BANK-002',1));same(false,$finance->payout($order,1,400,'BANK-002',1));same(600,$finance->balance(1,'shipping_payable:1'));
});
test('Cancellation releases remaining holds',function() use($allocation){$allocation->cancel(1);same(0,$allocation->outstanding('A','alex'));same(0,$allocation->outstanding('B','aswan'));});
test('Partial cancellation and unshipped refunds release holds without replay duplication',function() use($allocation,$db,$plan){
    $db->insert('sales_order',['entity_id'=>2]); $allocation->save(2,$plan);
    $quantities=['A'=>['canceled'=>200,'refunded'=>300]];
    $allocation->reconcile(2,$quantities); $allocation->reconcile(2,$quantities);
    same(500,$allocation->outstanding('A','alex'));
    $allocation->cancel(2);
});
test('Largest remainder preserves every cent',function(){ $s=new MoneySplit();same(['a'=>1,'b'=>1,'c'=>0],$s->allocate(2,['c'=>1,'b'=>1,'a'=>1])); foreach(range(0,200) as $n) same($n,array_sum($s->allocate($n,['a'=>13,'b'=>17,'c'=>29]))); });
$db->query('CREATE TABLE IF NOT EXISTS ves_vendor_entity (entity_id INT PRIMARY KEY) ENGINE=InnoDB');
$db->insertOnDuplicate('ves_vendor_entity',['entity_id'=>1]);
$db->insertOnDuplicate('ves_vendor_entity',['entity_id'=>2]);
$scope=new class implements Magento\Framework\App\Config\ScopeConfigInterface {
    public function getValue($path=null,$scopeType=self::SCOPE_TYPE_DEFAULT,$scopeCode=null) {
        return $path==='hubfulfillment/general/policy'?json_encode(['version'=>1,'currency'=>'AED','minor_digits'=>2,'sources'=>[],'products'=>[],'rates'=>[],
            'vendors'=>[['vendor_id'=>1,'modes'=>['vendor','marketplace']],['vendor_id'=>2,'modes'=>['marketplace']]]]):'kgs';
    }
    public function isSetFlag($path,$scopeType=self::SCOPE_TYPE_DEFAULT,$scopeCode=null){return false;}
};
$configuration=new MagentoEgypt\Fulfillment\Model\Configuration($scope,$resource,new Policy());
$dispatch=new MagentoEgypt\Fulfillment\Model\Dispatch($resource,$journal,$workflow,$configuration,$scope);
$db->insert('sales_order',['entity_id'=>3]);
$legacy=new Magento\Framework\DataObject(['id'=>3,'state'=>'processing','increment_id'=>'QA-LEGACY-003','base_currency_code'=>'AED','all_items'=>[
    new Magento\Framework\DataObject(['sku'=>'QA-A','vendor_id'=>1,'qty_ordered'=>1,'weight'=>null]),
    new Magento\Framework\DataObject(['sku'=>'QA-B','vendor_id'=>2,'qty_ordered'=>2,'weight'=>0.5])]]);
$event=fn($group,$action,$version,$key,$extra=[])=>json_encode(array_merge(['order_id'=>3,'group_id'=>$group,'action'=>$action,'expected_version'=>$version,'operation_key'=>$key],$extra));
test('Legacy order is unclassified without invented origin, parcels or weight',function() use($dispatch,$legacy){
    $g=$dispatch->describe($legacy)['groups'][0];same(null,$g['responsibility']);same(null,$g['source']);same(null,$g['parcel_count']);same(null,$g['items'][0]['weight_grams']);same('legacy_unallocated',$g['source_state']);
});
test('Cross-vendor dispatch mutation rejected',function() use($dispatch,$legacy,$event){rejects(fn()=>$dispatch->apply($legacy,$event('legacy-vendor-2','classify',0,'cross-vendor-001',['responsibility'=>'vendor']),1));});
test('Vendor responsibility follows policy and classifies only its own group',function() use($dispatch,$legacy,$event){
    rejects(fn()=>$dispatch->apply($legacy,$event('legacy-vendor-2','classify',0,'policy-denied-001',['responsibility'=>'vendor']),2));
    same(true,$dispatch->apply($legacy,$event('legacy-vendor-1','classify',0,'classify-owner-001',['responsibility'=>'vendor']),1)['applied']);
});
test('Fleet has monitoring only for vendor-managed groups',function() use($dispatch,$legacy,$event){
    same([],$dispatch->describe($legacy)['groups'][0]['supported_actions']);
    rejects(fn()=>$dispatch->apply($legacy,$event('legacy-vendor-1','ship',1,'fleet-denied-001',['execution'=>'own'])));
});
$ship=$event('legacy-vendor-1','ship',1,'vendor-shipped-001',['execution'=>'third_party','carrier'=>'QA Courier','tracking'=>'QA-AWB-001']);
test('Third-party report requires carrier and tracking and does not deduct inventory',function() use($dispatch,$legacy,$event,$ship,$db){
    rejects(fn()=>$dispatch->apply($legacy,$event('legacy-vendor-1','ship',1,'missing-awb-001',['execution'=>'third_party','carrier'=>'QA Courier']),1));
    $r=$dispatch->apply($legacy,$ship,1);same('shipped_reported',$r['group']['state']);same(2,$r['group']['version']);
    same(0,(int)$db->fetchOne('SELECT COUNT(*) FROM me_fulfillment_allocation WHERE order_id=3'));
});
test('Dispatch replay is idempotent and stale/conflicting events rejected',function() use($dispatch,$legacy,$ship,$event){
    same(false,$dispatch->apply($legacy,$ship,1)['applied']);
    rejects(fn()=>$dispatch->apply($legacy,str_replace('QA-AWB-001','QA-AWB-002',$ship),1));
    rejects(fn()=>$dispatch->apply($legacy,$event('legacy-vendor-1','delivered',1,'stale-event-001'),1));
    same('delivered_reported',$dispatch->apply($legacy,$event('legacy-vendor-1','delivered',2,'delivered-event-001'),1)['group']['state']);
});
test('Vendor can prepare marketplace group but cannot dispatch it',function() use($dispatch,$legacy,$event){
    $dispatch->apply($legacy,$event('legacy-vendor-2','classify',0,'marketplace-group-001',['responsibility'=>'marketplace']));
    same(['ready'],$dispatch->describe($legacy,2)['groups'][0]['supported_actions']);
    same('ready',$dispatch->apply($legacy,$event('legacy-vendor-2','ready',1,'ready-pickup-001'),2)['group']['state']);
    rejects(fn()=>$dispatch->apply($legacy,$event('legacy-vendor-2','ship',2,'vendor-forbidden-001',['execution'=>'own']),2));
});
$failed=count(array_filter($results,fn($r)=>$r['status']==='FAIL'));
echo json_encode(['suite'=>'Isolated synthetic MySQL fulfillment lifecycle','database'=>$config['dbname'],'passed'=>count($results)-$failed,'failed'=>$failed,'cases'=>$results],JSON_PRETTY_PRINT).PHP_EOL;
exit($failed?1:0);
