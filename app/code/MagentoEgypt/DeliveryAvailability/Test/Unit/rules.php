<?php
require dirname(__DIR__, 2).'/Model/Rules.php';
$engine=new MagentoEgypt\DeliveryAvailability\Model\Rules();
$rules=$engine->parse('[{"country":"EG","status":"green","sku":"*"},{"country":"EG","city_id":12,"status":"red","sku":"A"},{"country":"EG","city_id":12,"locality_id":13,"status":"blacklist","sku":"A"}]');
$cases=[['EG',12,13,'A','blacklist'],['EG',12,0,'A','red'],['EG',12,13,'B','green'],['AE',12,13,'A','unconfigured'],['EG',99,0,'A','green']];
foreach($cases as [$country,$city,$locality,$sku,$expected])if($engine->evaluate($rules,$country,$city,$locality,$sku)!==$expected)throw new RuntimeException('Rule precedence failed');
foreach(['{}','[{"country":"XX","status":"green","sku":"*"}]','[{"country":"EG","status":"unknown","sku":"*"}]','[{"country":"EG","status":"green","sku":"*","city_id":-1}]','[{"country":"EG","status":"green","sku":"*","locality_id":1}]'] as $bad){
 $failed=false;try{$engine->parse($bad);}catch(Throwable $e){$failed=true;}if(!$failed)throw new RuntimeException('Invalid rule accepted');
}
echo "PASS: 10 rule precedence and validation cases\n";

