<?php

namespace Vnecoms\VendorsCommissionPreview\Controller\Vendors\Preview;

use Vnecoms\VendorsCommission\Model\Rule as CommissionRule;

class Index extends \Vnecoms\Vendors\Controller\AbstractAction
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;
    
    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;
    
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_session;
    
    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var \Magento\CatalogRule\Model\RuleFactory
     */
    protected $catalogRuleFactory;
    
    /**
     * @var \Vnecoms\VendorsCommission\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    /**
     * @param \Vnecoms\Vendors\App\Action\Frontend\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Vnecoms\VendorsCommission\Model\RuleFactory $ruleFactory
     * @param \Magento\CatalogRule\Model\RuleFactory $catalogRuleFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Frontend\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Vnecoms\VendorsCommission\Model\RuleFactory $ruleFactory,
        \Magento\CatalogRule\Model\RuleFactory $catalogRuleFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer

    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_session = $context->getVendorSession();
        $this->ruleFactory = $ruleFactory;
        $this->catalogRuleFactory = $catalogRuleFactory;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
    }
    
    /**
     * @return void
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $productData = $this->getRequest()->getParam('product');
        $productData = json_decode($productData, true);
        try{
            if(!$productData) throw new \Exception(__("Product data is not valid"));
            
            $vendor = $this->_session->getVendor();
            $product = $this->_objectManager->create('Magento\Catalog\Model\Product');
            $product->setData('tmp_category_ids', $product->getData('category_ids'));
            $product->setData($productData);
            $product->setVendorId($this->_vendorSession->getVendor()->getId());
            $websiteId     = $this->storeManager->getStore()->getWebsiteId();

            $ruleCollection = $this->ruleFactory->create()->getCollection()
                ->addFieldToFilter('vendor_group_ids', ['finset'=>$vendor->getGroupId()])
                ->addFieldToFilter('website_ids', ['finset'=>$websiteId])
                ->addFieldToFilter('is_active', CommissionRule::STATUS_ENABLED);
             
            $today = (new \DateTime())->format('Y-m-d');
            $ruleCollection->getSelect()
            ->where(
                '(from_date IS NULL OR from_date<=?) AND (to_date IS NULL OR to_date>=?)',
                $today,
                $today
            )->order('priority ASC');
                         
            $fee = 0;
            if ($ruleCollection->count()) {
                foreach ($ruleCollection as $rule) {
                    $tmpRule = $this->catalogRuleFactory->create();
                    /*If the product is not match with the conditions just continue*/
                    $conditionSerialized = str_replace(
                        'Magento\\\\CatalogRule\\\\Model\\\\Rule\\\\Condition\\\\Product',
                        'Vnecoms\\\\VendorsCommissionPreview\\\\Model\\\\Condition\\\\Commission',
                        $rule->getConditionSerialized()
                    );
					if(!json_decode($rule->getConditionSerialized())){
						$conditionSerialized = $this->serializer->unserialize($rule->getConditionSerialized());
						$conditionSerialized = json_encode($conditionSerialized);
						$conditionSerialized = str_replace(
						    "Magento\\CatalogRule\\Model\\Rule\\Condition\\Product",
						    "Vnecoms\\VendorsCommissionPreview\\Model\\Condition\\Commission",
						    $rule->getConditionSerialized()
					    );
						$conditionSerialized = json_decode($conditionSerialized, true);
						$conditionSerialized = $this->serializer->serialize($rule->getConditionSerialized());
					}
					
                    $tmpRule->setConditionsSerialized($conditionSerialized);
                    if (!$tmpRule->getConditions()->validate($product)) {
                        continue;
                    }
                    $tmpFee = 0;
                    switch ($rule->getData('commission_by')) {
                        case CommissionRule::COMMISSION_BY_FIXED_AMOUNT:
                            $tmpFee = $rule->getData('commission_amount');
                            break;
                        case CommissionRule::COMMISSION_BY_PERCENT_PRODUCT_PRICE:
                            $amount = $product->getPrice();
                            $tmpFee = ($rule->getData('commission_amount') * $amount)/100;
                            break;
                    }
            
                    $fee +=  $tmpFee;
            
                    /*Break if the flag stop rules processing is set to 1*/
                    if ($rule->getData('stop_rules_processing')) {
                        break;
                    }
                }
            }
            
            $fee = $this->storeManager->getStore()->getBaseCurrency()->formatPrecision($fee, 2, [], false);
            
            $response->setData([
                'error' => false,
                'commission' => $fee,
            ]);
        }catch (\Exception $e){
            $response->setData([
                'error' => true,
                'msg' => $e->getMessage()
            ]);
        }
        
        return $this->_resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
