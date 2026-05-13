<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Model;

use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface as ProductItemInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Vnecoms\Quotation\Api\Data\ItemInterface;
use Vnecoms\Quotation\Api\ProposalRepositoryInterface;

/**
 *  * Price attributes:
 *  - price - initial item price, declared during product association
 *  - original_price - product price before any calculations
 *  - calculation_price - prices for item totals calculation

 * Class Item
 * @package Vnecoms\Quotation\Model
 *
 * @method Item setBuyRequest ($request)
 * @method Item setCreatedAt($time)
 * @method Item setUpdatedAt($time)
 * @method int getStoreId()
 * @method Item setStoreId(int $value)
 * @method Item setSku($value)
 * @method string getSKu
 * @method string getName()
 * @method Item setName($name)
 * @method string getParentItemId()
 * @method Item setParentItemId($id)
 * @method Item setProductType($type)
 * @method string getProductType
 * @method Item setPrice($price)
 * @method Item setBasePrice($price)
 * @method Item setDefaultProposal($default)
 * @method string getComment()
 * @method Item setComment($comment)
 * @method Item setRowTotal($total)
 * @method float getRowTotal()
 * @method Item setBaseRowTotal($total)
 * @method float getBaseRowTotal()
 * @method Item setOptions($options)
 */
class Item extends \Magento\Framework\Model\AbstractModel implements ItemInterface, ProductItemInterface
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'ves_quotation_item';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'item';

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var \Vnecoms\Quotation\Model\ResourceModel\Proposal\Collection
     */
    protected $_proposals;

    /**
     * @var Item|null
     */
    protected $_parentItem = null;

    /**
     * @var string
     */
    protected $_usedAttributes = '_cache_instance_used_attributes';

    /**
     * @var \Vnecoms\Quotation\Model\Item[]
     */
    protected $_children = [];

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $_localeFormat;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $om;

    /**
     * @var ResourceModel\Proposal\CollectionFactory
     */
    protected $proposalCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProposalFactory
     */
    protected $proposalFactory;

    /**
     * @var \Vnecoms\Quotation\Api\ProposalRepositoryInterface
     */
    protected $proposalRepository;

    /**
     * Default Proposal Object
     * @var Proposal
     */
    protected $defaultProposal;

    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $helper;

    /**
     * @var array
     */
    protected $_proposalsArray = [];

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    public function __construct(
        \Vnecoms\Quotation\Model\ResourceModel\Proposal\CollectionFactory $proposalCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Locale\FormatInterface $format,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\Quotation\Model\ProposalFactory $proposalFactory,
        \Vnecoms\Quotation\Api\ProposalRepositoryInterface $proposalRepository,
        \Vnecoms\Quotation\Helper\Data $helper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->timezone = $timezone;
        $this->productRepository = $productRepository;
        $this->proposalCollectionFactory = $proposalCollectionFactory;
        $this->_localeFormat = $format;
        $this->appState = $context->getAppState();
        $this->storeManager = $storeManager;
        $this->proposalFactory = $proposalFactory;
        $this->proposalRepository = $proposalRepository;
        $this->helper = $helper;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Get item_id
     * @return string
     */
    public function getItemId()
    {
        return $this->getData(self::ITEM_ID);
    }

    /**
     * @return \Magento\Framework\App\ObjectManager
     */
    public function getOm()
    {
        if (!$this->om) {
            $this->om = \Magento\Framework\App\ObjectManager::getInstance();
        }
        return $this->om;
    }

    /**
     * Set item_id
     * @param string $itemId
     * @return \Vnecoms\Quotation\Api\Data\ItemInterface
     */
    public function setItemId($itemId)
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    /**
     * Get quote_id
     * @return string
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setProductId($id)
    {
        return $this->setData('product_id', $id);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $request
     * @return $this
     */
    public function init($product, $request)
    {
        if ($this->appState->getAreaCode() === \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            $storeId = $this->storeManager->getStore($this->storeManager->getStore()->getId())
                ->getId();
            $this->setStoreId($storeId);
        } else {
            $this->setStoreId($this->storeManager->getStore()->getId());
        }

        /**
         * We can't modify existing child items
         */
        if ($this->getId() && $product->getParentProductId()) {
            return $this;
        }

        $this->setProduct($product);

        $this->setOptions($this->prepareCustomOptions($product));
        $this->setBuyRequest($product->getCustomOption('info_buyRequest')->getValue());
        return $this;
    }
    
    /**
     * @param $product
     * @return string
     */
    public function prepareCustomOptions($product)
    {
        $options = [];
        foreach ($product->getCustomOptions() as $option) {
            $options[$option->getCode()] = $option->getValue();
        }
        return serialize($options);
    }

    /**
     * Get child items
     *
     * @return \Vnecoms\Quotation\Model\Item[]
     */
    public function getChildren()
    {
        return $this->_children;
    }

    /**
     * Specify parent item id before saving data
     *
     * @return $this
     */
    public function beforeSave()
    {
        if ($this->isObjectNew()) {
            $this->setCreatedAt(date('Y-m-d H:i:s', $this->timezone->scopeTimeStamp()));
        }
        $this->setUpdatedAt(date('Y-m-d H:i:s', $this->timezone->scopeTimeStamp()));
        $this->setIsVirtual($this->getProduct()->getIsVirtual());

        if ($this->getQuote()) {
            $this->setQuoteId($this->getQuote()->getId());
        }
        if ($this->getParentItem()) {
            $this->setParentItemId($this->getParentItem()->getId());
        }

        parent::beforeSave();
        return $this;
    }

    /**
     * After save
     * 
     * @see \Magento\Framework\Model\AbstractModel::afterSave()
     */
    public function afterSave()
    {
        parent::afterSave();
        $this->saveProposals();
        return $this;
    }

    /**
     * Save all proposals
     * 
     * @return \Vnecoms\Quotation\Model\Item
     */
    public function saveProposals()
    {
        foreach($this->getProposalsCollection() as $proposal) {
            $proposal->setItem($this)->save();
        }
        return $this;
    }

    /**
     * Retrieve quote model object
     *
     * @param void
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuote()
    {
        if(!$this->quote){
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->quote = $om->create('Vnecoms\Quotation\Model\Quote')->load($this->getQuoteId());
        }
        return $this->quote;
    }

    /**
     * Declare quote model object
     *
     * @param   \Vnecoms\Quotation\Model\Quote $quote
     * @return $this
     */
    public function setQuote(\Vnecoms\Quotation\Model\Quote $quote)
    {
        $this->quote = $quote;
        $this->setQuoteId($quote->getId());
        $this->setStoreId($quote->getStoreId());
        return $this;
    }

    /**
     * Set quote_id
     * @param string $quote_id
     * @return \Vnecoms\Quotation\Api\Data\ItemInterface
     */
    public function setQuoteId($quote_id)
    {
        return $this->setData(self::QUOTE_ID, $quote_id);
    }

    /**
     * Get parent item
     *
     * @return Item
     */
    public function getParentItem()
    {
        return $this->_parentItem;
    }

    /**
     * Set parent item
     *
     * @param  Item $parentItem
     * @return $this
     */
    public function setParentItem($parentItem)
    {
        if ($parentItem) {
            $this->_parentItem = $parentItem;
            $parentItem->addChild($this);
        }
        return $this;
    }

    /**
     * Add child item
     *
     * @param  \Vnecoms\Quotation\Model\Item $child
     * @return $this
     */
    public function addChild($child)
    {
        $this->_children[] = $child;
        return $this;
    }

    /**
     * Clone quote item
     *
     * @return $this
     */
    public function __clone()
    {
        $this->setId(null);
        $this->_parentItem = null;
        $this->_children = [];
        $this->quote = null;
        return $this;
    }

    /**
     * Returns formatted buy request - object, holding request received from
     * product view page with keys and options for configured product
     *
     * @return \Magento\Framework\DataObject
     */
    public function getBuyRequest()
    {
        $option = $this->getOptionByCode('info_buyRequest');
        
        if($option){
            $value = json_decode($option->getValue(), true);
            if(!$value){
                $value = unserialize($option->getValue());
            }
            $buyRequest = new \Magento\Framework\DataObject($value);
        }else{
            $buyRequest = new \Magento\Framework\DataObject();
        }
        
        // Overwrite standard buy request qty, because item qty could have changed since adding to quote
        $buyRequest->setOriginalQty($buyRequest->getQty())->setQty($this->getQty() * 1);

        return $buyRequest;
    }

    /**
     * @return \Vnecoms\Quotation\Model\ResourceModel\Proposal\Collection
     */
    public function getProposalsCollection()
    {
        if (!$this->_proposals) {
            $this->_proposals = $this->proposalCollectionFactory->create()->setOrder('proposal_id','asc');
            $this->_proposals->setItemFilter($this);
        }
        return $this->_proposals;
    }
    
    /**
     * Prepare quantity
     *
     * @param float|int $qty
     * @return int|float
     */
    protected function _prepareQty($qty)
    {
        $qty = $this->_localeFormat->getNumber($qty);
        $qty = $qty > 0 ? $qty : 1;
        return $qty;
    }
    
    
    /**
     * @param float $qty
     * @param float $price
     * @param boolean $isDefault
     * 
     * @return \Vnecoms\Quotation\Model\Item
     */
    public function addProposal($qty, $price, $isDefault = false)
    {
        if($this->getParentItem()) return $this;
        
        if($isDefault){
            foreach($this->getProposalsCollection() as $proposal){
                if($proposal->getIsDefault()) $proposal->setIsDefault(false);
            }
        }
        $qty = $this->_prepareQty($qty);
        $proposal = $this->proposalFactory->create();
        $proposal->setData([
            'qty' => $qty,
            'price' => $this->getStore()->getBaseCurrency()->convert($price, $this->getStore()->getCurrentCurrency()),
            'base_price' => $price,
            'is_default' => $isDefault,
        ])->setItem($this);

        $this->getProposalsCollection()->addItem($proposal);
        return $this;
    }

    /**
     * Add Qty to the item
     * 
     * @param number $qty
     */
    public function addQty($qty = 0){
        $this->getDefaultProposal()->addQty($qty);
    }
    
    /**
     * @return Proposal|null
     */
    public function getDefaultProposal()
    {
        if (!$this->defaultProposal) {
            foreach ($this->getProposalsCollection() as $item) {
                if ($item->getIsDefault()) {
                    $this->defaultProposal = $item;
                    break;
                }
            }
        }
        return $this->defaultProposal;
    }

    /**
     * @param int $id
     * @return \Vnecoms\Quotation\Model\Item
     */
    public function removeProposal($id)
    {
        $proposal = $this->getProposalById($id);        
        if ($proposal) {
            if($proposal->isDefault()) throw new \Exception(__("You can not remove default proposal"));
            
            $proposal->setItem($this);
            $proposal->isDeleted(true);
        }
        return $this;
    }

    /**
     * @param $id
     * @return \Magento\Framework\DataObject
     */
    public function getProposalById($id)
    {
        return $this->getProposalsCollection()->getItemById($id);
    }

    /**
     * Check product representation in item
     *
     * @param   \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\DataObject $request
     * @return  bool
     */
    public function representProduct($product)
    {
        $itemProduct = $this->getProduct();
        if (!$product || $itemProduct->getId() != $product->getId()) {
            return false;
        }

        /**
         * Check maybe product is planned to be a child of some quote item - in this case we limit search
         * only within same parent item
         */
        $stickWithinParent = $product->getStickWithinParent();
        if ($stickWithinParent) {
            if ($this->getParentItem() !== $stickWithinParent) {
                return false;
            }
        }

        // Check options
        $itemOptions = $this->getOptionsByCode();
        $productOptions = $product->getCustomOptions();

        if (!$this->compareOptions($itemOptions, $productOptions)) {
            return false;
        }
        if (!$this->compareOptions($productOptions, $itemOptions)) {
            return false;
        }
        return true;
    }

    /**
     * get options like
     * [
     *  $code => Object($value)
     * ]
     * @return array
     */
    public function getOptionsByCode()
    {
        $return = [];
        foreach (unserialize($this->getOptions()) as $id => $option) {
            $data = ['code' => $id, 'value' => $option];
            /* Fix price calculation of configurable product*/
            if($id == 'simple_product'){
                $data['product'] = $this->productRepository->getById($option, false);
            }
            $return[$id] = new \Vnecoms\Quotation\Model\Item\Option($data);
        }

        return $return;
    }

    public function getOptions()
    {
        return $this->_getData('options');
    }

    /**
     * Check if two options array are identical
     * First options array is prerogative
     * Second options array checked against first one
     *
     * @param array $options1
     * @param array $options2
     * @return bool
     */
    public function compareOptions($options1, $options2)
    {
        foreach ($options1 as $option) {
            $code = $option->getCode();
            if (in_array($code, ['info_buyRequest'])) {
                continue;
            }
            if (!isset($options2[$code]) || $options2[$code]->getValue() != $option->getValue()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Retrieve product model object associated with item
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        $product = $this->_getData('product');
        if ($product === null && $this->getProductId()) {
            $product = clone $this->productRepository->getById(
                $this->getProductId(),
                false,
                $this->getQuote()->getStoreId()
            );
            $this->setProduct($product);
        }

        /**
         * Reset product final price because it related to custom options
         */
        $product->setFinalPrice(null);
        if (is_array($this->getOptionsByCode())) {
            $product->setCustomOptions($this->getOptionsByCode());
        }

        return $product;
    }

    /**
     * Setup product for item
     *
     * @param \Magento\Catalog\Model\Product|\Magento\Catalog\Api\Data\ProductInterface $product
     * @return $this
     */
    public function setProduct($product)
    {
        if ($this->getQuote()) {
            $product->setStoreId($this->getQuote()->getStoreId());
            $product->setCustomerGroupId($this->getQuote()->getCustomerGroupId());
        }
        $this->setData('product', $product)
            ->setProductId($product->getId())
            ->setProductType($product->getTypeId())
            ->setSku($product->getSku())
            ->setName($product->getName());

        if ($product->isVirtual()) {
            $this->setData('is_virtual',1);
        }

        return $this;
    }

    /**
     * @return integer
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * Compare items from an item
     *
     * @param   \Vnecoms\Quotation\Model\Item $item
     * @return  bool
     */
    public function compare($item)
    {
        if ($this->getProductId() != $item->getProductId()) {
            return false;
        }
        $targetOptions = $this->getOptions();
        $comparedOptions = $item->getOptions();

        if(
            (!is_array($targetOptions) && !is_array($comparedOptions))
        ) {
            return true;
        }
        
        if (
            (!is_array($targetOptions) && is_array($comparedOptions)) ||
            (is_array($targetOptions) && !is_array($comparedOptions)) ||
            array_diff_key($targetOptions, $comparedOptions) != array_diff_key($comparedOptions, $targetOptions)
        ) {
            return false;
        }
        foreach ($targetOptions as $name => $value) {
            if ($comparedOptions[$name] != $value) {
                return false;
            }
        }
        return true;
    }

    public function getCookingOptions($options)
    {

    }

    /**
     * Returns option values adopted to compare
     *
     * @param mixed $value
     * @return mixed
     */
    protected function getOptionValues($value)
    {
        if (is_string($value) && is_array(@unserialize($value))) {
            $value = @unserialize($value);
            unset($value['qty'], $value['uenc']);
            $value = array_filter($value, function ($optionValue) {
                return !empty($optionValue);
            });
        }
        return $value;
    }

    /**
     * Retrieve store model object
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->getQuote()->getStore();
    }

    /**
     * Convert Quote Item to array
     *
     * @param array $arrAttributes
     * @return array
     */
    public function toArray(array $arrAttributes = [])
    {
        $data = parent::toArray($arrAttributes);

        $product = $this->getProduct();
        if ($product) {
            $data['product'] = $product->toArray();
        }
        return $data;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\Quotation\Model\ResourceModel\Item');
    }

    /**
     * Get default proposal qty
     * 
     * @return number
     */
    public function getQty()
    {
        if ($this->getDefaultProposal())
            return (float) $this->getDefaultProposal()->getQty();
        return 0;
    }

    /**
     * Set qty for default proposal
     * 
     * @param number $qty
     * @return \Vnecoms\Quotation\Model\Item
     */
    public function setQty($qty = 0){
        if($proposal = $this->getDefaultProposal()){
            $proposal->setQty($qty);
        }
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getCalculationPrice()
    {
        $price = $this->_getData('calculation_price');
        if ($price === null) {
            if ($this->hasCustomPrice()) {
                $price = $this->getCustomPrice();
            } else {
                $price = $this->getConvertedPrice();
            }
            $this->setData('calculation_price', $price);
        }
        return $price;
    }

    /**
     * Calculate row total by proposal and qty
     * @return $this
     */
    public function calcRowTotal()
    {
        $qty = $this->getTotalQty();
        $total = $this->priceCurrency->roundPrice($this->getDefaultProposal()->getPrice(), 2) * $qty;
        $baseTotal = $this->priceCurrency->roundPrice($this->getDefaultProposal()->getBasePrice(), 2) * $qty;

        $this->setRowTotal($this->priceCurrency->roundPrice($total, 2));
        $this->setBaseRowTotal($this->priceCurrency->roundPrice($baseTotal, 2));

        return $this;
    }

    /**
     * Get total qty include parent item relation
     * @return float|null|string
     */
    public function getTotalQty()
    {
        if ($this->getParentItem()) {
            return $this->getQty() * $this->getParentItem()->getQty();
        }
        return $this->getQty();
    }


    //for ProductItemInterface
    public function getFileDownloadParams()
    {
        return null;
    }

    /**
     * @param string $code
     * @return array|string
     */
    public function getOptionByCode($code)
    {
        $options = $this->getOptionsByCode();

        return isset($options[$code]) ? $options[$code]: [];
    }

    /**
     * @return float|string
     */
    public function getPrice()
    {
        return $this->getDefaultProposal()?$this->getDefaultProposal()->getPrice():(float) 0;
    }

    /**
     * @return float|string
     */
    public function getBasePrice()
    {
        return $this->getDefaultProposal()?$this->getDefaultProposal()->getBasePrice():(float) 0;
    }

    /**
     * check proposal has
     * @return bool
     */
    public function hasDefaultProposal()
    {
        return ($this->getDefaultProposal() == null) ? false : true;
    }
}
