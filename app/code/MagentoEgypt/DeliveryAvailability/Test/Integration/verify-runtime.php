<?php
declare(strict_types=1);
require dirname(__DIR__, 6).'/app/bootstrap.php';
$om=Magento\Framework\App\Bootstrap::create(BP,$_SERVER)->getObjectManager();
$om->get(Magento\Framework\App\State::class)->setAreaCode('frontend');
$directory=$om->get(MagentoEgypt\CityManager\Model\Directory::class);
$cities=$directory->options('EG','city');
if(!$cities)throw new RuntimeException('No Egyptian city fixtures');
$city=$cities[0];
$config=new class implements Magento\Framework\App\Config\ScopeConfigInterface {
 public string $rules='[]';
 public function getValue($path=null,$scopeType=self::SCOPE_TYPE_DEFAULT,$scopeCode=null){return $this->rules;}
 public function isSetFlag($path,$scopeType=self::SCOPE_TYPE_DEFAULT,$scopeCode=null){return false;}
};
$availability=new MagentoEgypt\DeliveryAvailability\Model\Availability($config,$directory,new MagentoEgypt\DeliveryAvailability\Model\Rules());
$location=$availability->location('EG',(int)$city['region_id'],(int)$city['location_id'],0);
if($availability->check($location,'TEST')['coverage']!=='unconfigured')throw new RuntimeException('Unconfigured coverage failed');
$quote=new class($city) {
 public bool $virtual=false;
 public function __construct(public array $city){}
 public function isVirtual(){return $this->virtual;}
 public function getShippingAddress(){return new Magento\Framework\DataObject(['country_id'=>'EG','region_id'=>$this->city['region_id'],'city'=>$this->city['name_en']]);}
 public function getAllItems(){return [new Magento\Framework\DataObject(['sku'=>'TEST','name'=>'Test item','product'=>new class {public function isVirtual(){return false;}}])];}
};
$repository=new class($quote) implements Magento\Quote\Api\CartRepositoryInterface {
 public function __construct(private object $quote){}
 public function get($cartId){return $this->quote;}
 public function getActive($cartId,array $sharedStoreIds=[]){return $this->quote;}
 public function getList(Magento\Framework\Api\SearchCriteriaInterface $searchCriteria){throw new LogicException('Not used');}
 public function getForCustomer($customerId,array $sharedStoreIds=[]){throw new LogicException('Not used');}
 public function getActiveForCustomer($customerId,array $sharedStoreIds=[]){throw new LogicException('Not used');}
 public function save(Magento\Quote\Api\Data\CartInterface $quote){throw new LogicException('No writes allowed');}
 public function delete(Magento\Quote\Api\Data\CartInterface $quote){throw new LogicException('No writes allowed');}
};
$guard=new MagentoEgypt\DeliveryAvailability\Plugin\PlaceOrder($repository,$availability);
$subject=(new ReflectionClass(Magento\Quote\Model\QuoteManagement::class))->newInstanceWithoutConstructor();
foreach(['green','red','blacklist'] as $status){
 $config->rules=json_encode([['country'=>'EG','city_id'=>(int)$city['location_id'],'sku'=>'TEST','status'=>$status]]);
 $blocked=false;
 try{$guard->beforePlaceOrder($subject,1);}catch(Magento\Framework\Exception\LocalizedException $e){$blocked=true;}
 if($blocked!==($status!=='green'))throw new RuntimeException('Checkout guard failed: '.$status);
}
$quote->virtual=true;
$guard->beforePlaceOrder($subject,1);
$quote->virtual=false;
$quote->city['name_en']='Unknown legacy city';
$config->rules='[{"country":"EG","sku":"UNRELATED","status":"blacklist"}]';
$guard->beforePlaceOrder($subject,1);
$rejected=false;try{$availability->location('AE',0,(int)$city['location_id'],0);}catch(Magento\Framework\Exception\LocalizedException $e){$rejected=true;}
if(!$rejected)throw new RuntimeException('Mismatched location accepted');
echo json_encode(['result'=>'PASS','checks'=>7,'city_id'=>$city['location_id'],'region_id'=>$city['region_id'],'writes'=>0,'orders_created'=>0]).PHP_EOL;

