<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model;

use Magento\Catalog\Model\ProductRepository;
use Vnecoms\Quotation\Api\Data\QuoteInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Class Quote
 * @method string getExpiredDate()
 * @method string getReminderDate()
 * @method Quote setReminderDate($reminderDate)
 * @method Quote setExpiredDate($expiredDate)
 * @method Quote setUpdatedAt($updatedAt)
 * @method Quote setCreatedAt($createdAt)
 * @method string getStoreName()
 * @method string getCurrencyCode()
 * @method string getQuoteCurrencyCode()
 * @method string getBaseCurrencyCode()
 * @method integer getCustomerIsGuest()
 * @method Quote setCustomerGroupId($groupId)
 * @method string getCustomerGroupName()
 * @method string getNote()
 * @method Quote setNote($note)
 * @method string getCouponCode()
 * @method Quote setCouponCode($code)
 * @method string getClientComment()
 * @method string getShippingDescription()
 * @method Quote setBaseCurrencyCode($code)
 * @method Quote setCurrencyCode($code)
 * @method Quote setQuoteCurrencyCode($code)
 * @method string getMaskId()
 * @method Quote setMaskId($id)
 * @method float getBaseGrandTotal()
 * @method Quote setBaseGrandTotal($total)
 * @method float getGrandTotal()
 * @method Quote setGrandTotal($total)
 * @method float getBaseSubtotal()
 * @method Quote setBaseSubtotal($total)
 * @method float getSubtotal()
 * @method Quote setSubtotal($total)
 * @method integer getItemsCount()
 * @method integer getItemsQty()
 * @method Quote setItemsCount($c)
 * @method Quote setItemsQty($c)
 * @method Quote setCustomerIsGuest($guest)
 *
 * @package Vnecoms\Quotation\Model
 */

class Quote extends \Magento\Framework\Model\AbstractModel implements QuoteInterface
{
    const STATUS_CREATED            = 10;   /* When the quote is created, first status */
    const STATUS_CREATED_NOT_SENT   = 11;   /* When admin create new quote. */
    const STATUS_PROCESSING     = 20;   /* When the customer submit the quote request*/
    const STATUS_HOLD           = 30;   /* Quote is currently on hold */
    const STATUS_CANCELLED      = 40;   /* When quote cancelled by admin or customer */
    const STATUS_SENT           = 50;   /* When admin send quote back to customer. */
    const STATUS_ACCEPTED       = 60;   /* Customer accept quote */
    const STATUS_REJECTED       = 70;   /* Customer reject quote */
    const STATUS_EXPIRED        = 80;   /* Expire quote, set by expire time */
    const STATUS_ORDERED        = 90;   /* When quote to order */

    /**
     * @var string
     */
    protected $_eventPrefix = 'ves_quotation_quote';

    /**
     * @var string
     */
    protected $_eventObject = 'quote';

    /**
     * @var bool|\Vnecoms\Quotation\Model\ResourceModel\Item\Collection
     */
    protected $_items;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var ResourceModel\Item\CollectionFactory
     */
    protected $_quoteItemCollectionFactory;

    /**
     * @var bool|Message[]
     */
    protected $_messages;

    /**
     * @var Message\CollectionFactory
     */
    protected $_messageCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    protected $currentStore;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;

    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $helper;

    /**
     * @var \Vnecoms\Quotation\Helper\Email
     */
    protected $emailHelper;

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    protected $config;

    /**
     * @var ItemFactory
     */
    protected $quoteItemFactory;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $randomDataGenerator;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vnecoms\Quotation\Model\ResourceModel\Item\CollectionFactory $collectionFactory
     * @param ResourceModel\Message\CollectionFactory $messageCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Vnecoms\Quotation\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config
     * @param ItemFactory $factory
     * @param \Magento\Framework\Math\Random $randomDataGenerator
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Vnecoms\Quotation\Helper\Email $email
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct
    (
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\Quotation\Model\ResourceModel\Item\CollectionFactory $collectionFactory,
        ResourceModel\Message\CollectionFactory $messageCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Vnecoms\Quotation\Helper\Data $helper,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config,
        ItemFactory $factory,
        \Magento\Framework\Math\Random $randomDataGenerator,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Vnecoms\Quotation\Helper\Email $email,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;
        $this->_quoteItemCollectionFactory = $collectionFactory;
        $this->_messageCollectionFactory = $messageCollectionFactory;
        $this->timezone = $timezone;
        $this->helper = $helper;
        $this->config = $config;
        $this->quoteItemFactory = $factory;
        $this->randomDataGenerator = $randomDataGenerator;
        $this->productRepository = $productRepository;
        $this->emailHelper = $email;
        $this->currencyFactory = $currencyFactory;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
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
     * @return Quote
     */
    public function generateMaskId()
    {
        return $this->setMaskId($this->randomDataGenerator->getUniqueHash());
    }

    /**
     * Set quote_id
     * @param string $quoteId
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @return string
     */
    public function getRemoteIp()
    {
        return $this->getData(self::IP_ADDR);
    }

    /**
     * @param string $ip
     */
    public function setRemoteIp($ip)
    {
        return $this->setData(self::IP_ADDR, $ip);
    }

    /**
     * Get increment_id
     * @return string
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * Retrieve customer group id
     *
     * @return int
     */
    public function getCustomerGroupId()
    {
        if ($this->hasData('customer_group_id')) {
            return $this->getData('customer_group_id');
        } elseif ($this->getCustomerId()) {
            return $this->getCustomer()->getGroupId();
        } else {
            return \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID;
        }
    }

    /**
     * Set increment_id
     * @param string $increment_id
     * @return \Vnecoms\Quotation\Api\Data\QuoteInterface
     */
    public function setIncrementId($increment_id)
    {
        return $this->setData(self::INCREMENT_ID, $increment_id);
    }

    /**
     * @return string
     */
    public function getTaxVat()
    {
        return $this->getData(self::TAX_VAT);
    }


    /**
     * Get Customer Name
     * @return string
     */
    public function getCustomerName()
    {
        if ($this->getFirstname()) {
            $customerName = $this->getFirstName() . ' ' . $this->getLastName();
        } else {
            $customerName = (string)__('Guest');
        }
        return $customerName;
    }

    /**
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @param $status
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getFirstName()
    {
        return $this->getData(self::FIRSTNAME);
    }

    public function getLastName()
    {
        return $this->getData(self::LASTNAME);
    }

    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    public function getStoreId()
    {
        if (!$this->hasStoreId()) {
            return $this->_storeManager->getStore()->getId();
        }
        return (int)$this->_getData(self::STORE_ID);
    }

    public function setStoreId($id)
    {
        return $this->setData(self::STORE_ID, (int) $id);
    }

    /**
     * Returns created_at
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(QuoteInterface::CREATED_AT);
    }

    /**
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(QuoteInterface::UPDATED_AT);
    }

    public function setFirstName($firstname)
    {
        return $this->setData(self::FIRSTNAME, $firstname);
    }

    public function setLastName($lastname)
    {
        return $this->setData(self::LASTNAME, $lastname);
    }

    public function setCustomerId($id)
    {
        return $this->setData(self::CUSTOMER_ID, $id);
    }

    public function setMiddleName($name)
    {
        return $this->setData('customer_middlename', $name);
    }

    /**
     * Retrieve store base currency
     *
     * @return Currency
     */
    public function getBaseCurrency()
    {
        $currency = $this->getData('base_currency');
        if (null === $currency) {
            $currency = $this->currencyFactory->create()->load($this->getBaseCurrencyCode());
            $this->setData('base_currency', $currency);
        }
        return $currency;
    }

    /**
     * Retrieve store base currency
     *
     * @return Currency
     */
    public function getQuoteCurrency()
    {
        $currency = $this->getData('quote_currency');
        if (null === $currency) {
            $currency = $this->currencyFactory->create()->load($this->getQuoteCurrencyCode());
            $this->setData('quote_currency', $currency);
        }
        return $currency;
    }

    /**
     * Format Price
     */
    public function formatPrice($price){
        return $this->getQuoteCurrency()->format($price);
    }

    /**
     * Retrieve text formatted price value including order rate
     *
     * @param   float $price
     * @return  string
     */
    public function formatPriceTxt($price)
    {
        return $this->getQuoteCurrency()->formatTxt($price);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\Quotation\Model\ResourceModel\Quote');
    }

    public function afterSave()
    {
        if ($this->getItemsCollection()) {
            $this->getItemsCollection()->save();
        }
        parent::afterSave();
    }

    /**
     * Get Reserved Quote Increment ID
     * @return string
     */
    protected function getReservedQuoteId()
    {
        return $this->helper->getReservedId($this);
    }


    /**
     * Prepare data before save
     *
     * @return $this
     */
    public function beforeSave()
    {
        if ($this->isObjectNew()) {
            $this->setCreatedAt(date('Y-m-d H:i:s', $this->timezone->scopeTimeStamp()));
            if(!$this->getStatus()) $this->setStatus(self::STATUS_CREATED);
        }

        /**
         * Currency logic
         *
         * global - currency which is set for default in backend
         * base - currency which is set for current website. all attributes that
         *      have 'base_' prefix saved in this currency
         * quote/order - currency which was selected by customer or configured by
         *      admin for current store. currency in which customer sees
         *      price thought all checkout.
         *
         * Rates:
         *      base_to_global & base_to_quote/base_to_order
         */
        if(!$this->getBaseCurrencyCode()){
            $baseCurrency = $this->getStore()->getBaseCurrency();
            $quoteCurrency = $this->getStore()->getCurrentCurrency();

            $this->setBaseCurrencyCode($baseCurrency->getCode());
            $this->setCurrencyCode($quoteCurrency->getCode());
            $this->setQuoteCurrencyCode($quoteCurrency->getCode());
        }

        $this->setUpdatedAt(date('Y-m-d H:i:s', $this->timezone->scopeTimeStamp()));

        if ($this->_customer) {
            $this->setCustomerId($this->_customer->getId());
        }

        parent::beforeSave();
    }

    /**
     * @return \Vnecoms\Quotation\Model\ResourceModel\Item\Collection
     */
    public function getItemsCollection()
    {
        if ($this->hasItemsCollection()) {
            return $this->getData('items_collection');
        }
        if (null === $this->_items) {
            $this->_items = $this->_quoteItemCollectionFactory->create();
            $this->_items->setQuoteFilter($this);
        }
        return $this->_items;
    }

    /**
     * Check quote has items
     * @return integer
     */
    public function hasItems()
    {
        return sizeof($this->getAllItems()) > 0;
    }

    /**
     * Retrieve item model object by item identifier
     *
     * @param   int $itemId
     * @return  \Vnecoms\Quotation\Model\Item|\Magento\Framework\DataObject
     */
    public function getItemById($itemId)
    {
        return $this->getItemsCollection()->getItemById($itemId);
    }

    /**
     * Checking product exist in Quote
     *
     * @param int $productId
     * @return bool
     */
    public function hasProductId($productId)
    {
        foreach ($this->getAllItems() as $item) {
            if ($item->getProductId() == $productId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param float|\Magento\Framework\DataObject|null $params
     */
    public function addProduct(\Magento\Catalog\Model\Product $product, $params=null)
    {
        if ($params === null) {
            $params = 1;
        }
        if (is_numeric($params)) {
            $params = new \Magento\Framework\DataObject(['qty' => $params]);
        }
        if (!$params instanceof \Magento\Framework\DataObject) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We found an invalid request for adding product to quote.')
            );
        }

        $cartCandidates = $product->getTypeInstance()->prepareForCartAdvanced($params, $product, \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL);

        /**
         * Error message
         */
        if (is_string($cartCandidates) || $cartCandidates instanceof \Magento\Framework\Phrase) {
            return strval($cartCandidates);
        }

        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = [$cartCandidates];
        }
        $parentItem = null;
        $item = null;
        foreach ($cartCandidates as $candidate) {
            $stickWithinParent = $candidate->getParentProductId() ? $parentItem : null;
            $candidate->setStickWithinParent($stickWithinParent);

            $item = $this->getItemByProduct($candidate); //todo

            if (!$item) {
                /**
                 * @var Item $item
                 */
                $item = $this->quoteItemFactory->create();
                $item->init($candidate, $params);
                // Add only item that is not in quote already
                $this->addItem($item);
            }

            /**
             * As parent item we should always use the item of first added product
             */
            if (!$parentItem) {
                $parentItem = $item;
            }
            if ($parentItem && $candidate->getParentProductId() && !$item->getParentItem()) {
                $item->setParentItem($parentItem);
            }

            /**
             * We specify qty after we know about parent (for stock)
             */
            if ($params->getResetCount() && !$candidate->getStickWithinParent() && $item->getId() == $params->getId()) {
                $item->setData('qty', 0);
            }
            if (!$item->hasDefaultProposal()) {
                $product = $item->getProduct();
                $product->setCustomerGroupId($this->getCustomerGroupId());
                $finalPrice = $product->getFinalPrice(1);
                $item->addProposal($candidate->getCartQty(), $finalPrice, true);
                $item->setBaseOriginPrice($finalPrice);
                $item->setOriginPrice(
                    $item->getStore()->getBaseCurrency()->convert($finalPrice, $item->getStore()->getCurrentCurrency())
                );

            } else {
                $item->addQty($candidate->getCartQty());
            }
        }
        return $parentItem;
    }

    /**
     * Mark all quote items as deleted (empty quote)
     *
     * @return $this
     */
    public function removeAllItems()
    {
        foreach ($this->getItemsCollection() as $itemId => $item) {
            if ($item->getId() === null) {
                $this->getItemsCollection()->removeItemByKey($itemId);
            } else {
                $item->isDeleted(true);
            }
        }
        return $this;
    }


    /**
     * Remove quote item by item identifier
     *
     * @param   int $itemId
     * @return $this
     */
    public function removeItem($itemId)
    {
        $item = $this->getItemById($itemId);

        if ($item) {
            $item->setQuote($this);
            $item->isDeleted(true);
            if ($item->getChildren()) {
                foreach ($item->getChildren() as $child) {
                    $child->isDeleted(true);
                }
            }

            $parent = $item->getParentItem();
            if ($parent) {
                $parent->isDeleted(true);
            }
        }

        return $this;
    }

    /**
     * get customer, if null, set to null
     * @return \Magento\Customer\Model\Customer|null
     */
    public function getCustomer()
    {

        if (null === $this->_customer) {
            try {
                $this->_customer = $this->customerFactory->create()->load($this->getCustomerId());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->_customer = $this->customerFactory->create();
                $this->_customer->setId(null);
            }
        }

        return $this->_customer;
    }

    /**
     * Set Customer to quote
     * @param CustomerInterface $customer
     * @return $this
     */
    public function setCustomer(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        $this->_customer = $customer;
        $this->setCustomerFirstName($customer->getFirstname());
        $this->setCustomerLastName($customer->getLastname());
        $this->setCustomerMiddleName($customer->getMiddlename());
        $this->setCustomerId($customer->getId());
        $this->setCustomerGroupId($customer->getGroupId());
        $this->setCustomerIsGuest(0);
        $this->setData('customer_email', $customer->getEmail())
            ->setData('customer_taxvat', $customer->getTaxvat())
            ->setData('customer_gender', $customer->getGender())
        ;

        return $this;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|\Magento\Store\Model\Store
     */
    public function getStore()
    {
        $storeId = $this->getStoreId();
        if ($storeId) {
            return $this->_storeManager->getStore($storeId);
        }
        return $this->_storeManager->getStore();
    }

    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     */
    public function setStore(\Magento\Store\Api\Data\StoreInterface $store)
    {
        $this->currentStore = $store;
        $this->setStoreId($store->getId());

        return $this;
    }

    /**
     * @return bool|Message[]
     */
    public function getMessagesCollection()
    {
        if (empty($this->_messages)) {
            if ($this->getId()) {
                $this->_messages = $this->_messageCollectionFactory->create()->setQuoteFilter($this);
            } else {
                return false;
            }
        }
        return $this->_messages;
    }

    /**
     * Check quote has message
     * @return integer
     */
    public function hasMessage()
    {
        return $this->getMessagesCollection()->count();
    }

    /**
     * @return bool
     */
    public function canUnhold()
    {
        return $this->getStatus() == self::STATUS_HOLD;
    }

    /**
     * @return bool
     */
    public function canHold()
    {
        return $this->getStatus() != self::STATUS_HOLD;
    }

    /**
     * @return boolean
     */
    public function canCancel(){
        return $this->getStatus() <= self::STATUS_PROCESSING;
    }

    /**
     * @return boolean
     */
    public function canApprove(){
        return $this->getStatus() <= self::STATUS_PROCESSING;
    }

    /**
     * @return string
     */
    public function getStatusBeforeHold()
    {
        return $this->getData(self::STATUS_BEFORE_HOLD);
    }

    public function setStatusBeforeHold($status)
    {
        return $this->setData(self::STATUS_BEFORE_HOLD, $status);
    }

    /**
     * Hold the quote
     */
    public function hold()
    {
        if ($this->canHold()) {
            $this->_eventManager->dispatch($this->_eventPrefix.'_hold_before', $this->_getEventData());
            $this->setStatusBeforeHold($this->getStatus());
            $this->setStatus(self::STATUS_HOLD);
            $this->save();
            $this->_eventManager->dispatch($this->_eventPrefix.'_hold_after', $this->_getEventData());
        }

        return $this;
    }

    /**
     * Un hold quote
     */
    public function unHold()
    {
        if ($this->canUnhold()) {
            $this->_eventManager->dispatch($this->_eventPrefix.'_unhold_before', $this->_getEventData());
            $this->setStatus($this->getStatusBeforeHold());
            $this->setStatusBeforeHold(null);
            $this->save();
            $this->_eventManager->dispatch($this->_eventPrefix.'_unhold_after', $this->_getEventData());
        }

        return $this;
    }

    /**
     * Cancel quote
     */
    public function cancel()
    {
        $this->_eventManager->dispatch($this->_eventPrefix.'_cancel_before', $this->_getEventData());
        $this->setStatus(self::STATUS_CANCELLED)->save();
        $this->_eventManager->dispatch($this->_eventPrefix.'_cancel_after', $this->_getEventData());
        return $this;
    }

    /**
     * Reject quote
     */
    public function reject()
    {
        $this->_eventManager->dispatch($this->_eventPrefix.'_reject_before', $this->_getEventData());
        $this->setStatus(self::STATUS_REJECTED)->save();
        $this->_eventManager->dispatch($this->_eventPrefix.'_reject_after', $this->_getEventData());
        return $this;
    }

    public function accept()
    {
        $this->_eventManager->dispatch($this->_eventPrefix.'_accept_before', $this->_getEventData());
        $this->setStatus(self::STATUS_ACCEPTED)->save();
        $this->_eventManager->dispatch($this->_eventPrefix.'_accept_after', $this->_getEventData());
        return $this;
    }

    /**
     * Admin approves the quote and send back to customer
     *
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function approve()
    {
        $this->_eventManager->dispatch($this->_eventPrefix.'_approve_before', $this->_getEventData());
        $this->setStatus(self::STATUS_SENT)->save();
        $this->_eventManager->dispatch($this->_eventPrefix.'_approve_after', $this->_getEventData());
        return $this;
    }

    /**
     * Submit the quote to customer|admin
     * @return $this
     */
    public function submit()
    {
        $this->_eventManager->dispatch($this->_eventPrefix.'_submit_before', $this->_getEventData());

        if($this->getStatus() >= self::STATUS_PROCESSING){
            throw new \Exception(__('Can not submit the quote'));
        }
        $this->setStatus(self::STATUS_PROCESSING);
        $this->setIsActive(0);
        $dateObj        = ObjectManager::getInstance()->create('Magento\Framework\Stdlib\DateTime\DateTime');
        $expirationTime = $this->helper->getExpirationTime($this->getStoreId());
        $reminderTime   = $this->helper->getReminderTime($this->getStoreId());
        $now            = $dateObj->date('Y-m-d');
        $this->setExpiredDate(
            $dateObj->date('Y-m-d',strtotime($now." +".$expirationTime." days"))
        );
        if($reminderTime) $this->setReminderDate(
            $dateObj->date('Y-m-d',strtotime($now." +".$reminderTime." days"))
        );

        $this->collectTotals();
        $this->save();
        $this->_eventManager->dispatch($this->_eventPrefix.'_submit_after', $this->_getEventData());
        return $this;
    }

    /**
     * Collect Totals
     * @return $this
     */
    public function collectTotals()
    {
        if ($this->getTotalsCollectedFlag()) {
            return $this;
        }

        $this->setBaseGrandTotal(0)
            ->setGrandTotal(0)
            ->setSubtotal(0)
            ->setBaseSubtotal(0)
            ->setItemsCount(0)
            ->setItemsQty(0);

        //collect qty
        foreach ($this->getAllVisibleItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $this->setItemsCount($this->getItemsCount() + 1);
            $this->setItemsQty((float)$this->getItemsQty() + $item->getQty());
        }

        //collect totals price
        foreach ($this->getItemsCollection() as $item) {
            $this->setBaseGrandTotal((float)($this->getBaseGrandTotal()+$item->getBaseRowTotal()));
            $this->setGrandTotal((float)($this->getGrandTotal()+$item->getRowTotal()));
            $this->setSubtotal((float)($this->getSubtotal()+$item->getRowTotal()));
            $this->setBaseSubtotal((float)($this->getBaseSubtotal()+$item->getBaseRowTotal()));
        }

        $this->setTotalsCollectedFlag(true);

        return $this;
    }

    /**
     * Get formatted order created date in store timezone
     *
     * @param   int $format date format type
     * @return  string
     */
    public function getCreatedAtFormatted($format)
    {
        return $this->timezone->formatDateTime(
            new \DateTime($this->getCreatedAt()),
            $format,
            $format,
            null,
            $this->timezone->getConfigTimezone('store', $this->getStore())
        );
    }

    /**
     * Loading quote data by customer
     *
     * @param \Magento\Customer\Model\Customer|int $customer
     * @return $this
     */
    public function loadByCustomer($customer)
    {
        if ($customer instanceof \Magento\Customer\Model\Customer || $customer instanceof CustomerInterface) {
            $customerId = $customer->getId();
        } else {
            $customerId = (int)$customer;
        }
        $this->_getResource()->loadByCustomerId($this, $customerId);
        $this->_afterLoad();
        return $this;
    }


    /**
     * Merge quotes
     *
     * @param   Quote $quote
     * @return $this
     */
    public function merge(Quote $quote)
    {
        $this->_eventManager->dispatch(
            $this->_eventPrefix . '_merge_before',
            [$this->_eventObject => $this, 'source' => $quote]
        );

        foreach ($quote->getAllItems() as $item) {
            $found = false;
            foreach ($this->getAllItems() as $quoteItem) {
                if ($quoteItem->compare($item)) {
                    $quoteItem->setQty($quoteItem->getQty() + $item->getQty())
                        ->setDataChanges(true);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $newItem = clone $item;
                foreach($item->getProposalsCollection() as $proposal){
                    $newItem->addProposal(
                        $proposal->getQty(),
                        $proposal->getPrice(),
                        $proposal->getIsDefault()
                    );
                }
                $this->addItem($newItem);
                if ($item->getHasChildren()) {
                    foreach ($item->getChildren() as $child) {
                        $newChild = clone $child;
                        $newChild->setParentItem($newItem);
                        $this->addItem($newChild);
                    }
                }
            }
        }

        $this->_eventManager->dispatch(
            $this->_eventPrefix . '_merge_after',
            [$this->_eventObject => $this, 'source' => $quote]
        );

        return $this;
    }

    /**
     * Get All items of collection
     * @return array
     */
    public function getAllItems()
    {
        $items = [];
        foreach ($this->getItemsCollection() as $item) {
            if (!$item->isDeleted()) {
                $item->calcRowTotal();
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Get array of all items what can be display directly (without parent item)
     *
     * @return \Vnecoms\Quotation\Model\Item[]
     */
    public function getAllVisibleItems()
    {
        $items = [];
        foreach ($this->getItemsCollection() as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                $item->calcRowTotal();
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Retrieve quote item by product id
     *
     * @param   \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\DataObject|null|float $request
     * @return  \Vnecoms\Quotation\Model\Item|bool
     */
    public function getItemByProduct($product)
    {
        foreach ($this->getAllItems() as $item) {
            if ($item->representProduct($product)) {
                return $item;
            }
        }
        return false;
    }

    /**
     * Adding new item to quote
     * @param Item $item
     * @return $this
     */
    public function addItem(\Vnecoms\Quotation\Model\Item $item)
    {
        $item->setQuote($this);
        if (!$item->getId()) {
            $this->getItemsCollection()->addItem($item);
        }
        return $this;
    }

    /**
     * Update Quote items from data POST
     * @param array $data
     * @return $this
     */
    public function updateItems(array $data)
    {
        $infoDataObject = new \Magento\Framework\DataObject($data);
        foreach ($data as $itemId => $itemInfo) {
            $item = $this->getItemById($itemId);
            if (!$item) {
                continue;
            }

            if (isset($itemInfo['comment'])) {
                $item->setComment($itemInfo['comment']);
            }

            if (!empty($itemInfo['remove']) || isset($itemInfo['qty']) && $itemInfo['qty'] == '0') {
                $this->removeItem($itemId);
                continue;
            }

            $qty = isset($itemInfo['qty']) ? (double)$itemInfo['qty'] : false;
            $defaultProposal = $item->getDefaultProposal();
            if ($qty > 0) {
                $defaultProposal->setQty($qty)->setId($defaultProposal->getId());
            }
            if(isset($itemInfo['price'])){
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $priceCurrencyFactory = $objectManager->get('Magento\Directory\Model\CurrencyFactory');
                $rate = $priceCurrencyFactory->create()->load(  $item->getStore()->getCurrentCurrency()->getCode())
                    ->getAnyRate($item->getStore()->getBaseCurrency()->getCode());

                $price = $itemInfo['price'] * $rate;

            	$defaultProposal->setPrice($itemInfo['price'])
                    ->setBasePrice($price)
                    ->setId($defaultProposal->getId());
            }
            $defaultProposal->save();
        }

        return $this;
    }

    /**
     * Returns suggested quantities for items.
     * Can be used to automatically fix user entered quantities before updating quote
     * so that quote contains valid qty values
     *
     * The $data is an array of ($quoteItemId => (item info array with 'qty' key), ...)
     *
     * @param   array $data
     * @return  array
     */
    public function suggestItemsQty($data)
    {
        foreach ($data as $itemId => $itemInfo) {
            if (!isset($itemInfo['qty'])) {
                continue;
            }
            $qty = (float)$itemInfo['qty'];
            if ($qty <= 0) {
                continue;
            }

            $quoteItem = $this->getItemById($itemId);
            if (!$quoteItem) {
                continue;
            }

            $product = $quoteItem->getProduct();
            if (!$product) {
                continue;
            }
            $data[$itemId]['qty'] = 1;
        }
        return $data;
    }

    /**
     * @param $itemId
     * @param $buyRequest
     * @param null $params
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateItem($itemId, $buyRequest, $params = null)
    {
        $item = $this->getItemById($itemId);
        if (!$item) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('This is the wrong quote item id to update configuration.')
            );
        }
        $productId = $item->getProduct()->getId();
        //We need to create new clear product instance with same $productId
        //to set new option values from $buyRequest
        $product = clone $this->productRepository->getById($productId, false, $this->getStore()->getId());

        if (!$params) {
            $params = new \Magento\Framework\DataObject();
        } elseif (is_array($params)) {
            $params = new \Magento\Framework\DataObject($params);
        }
    }

    /**
     * @return int|mixed
     */
    public function getItemsSummaryQty()
    {
        $qty = $this->getData('all_items_qty');
        if (null === $qty) {
            $qty = 0;
            foreach ($this->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }

                $children = $item->getChildren();
                if ($children) {
                    foreach ($children as $child) {
                        $qty += $child->getQty() * $item->getQty();
                    }
                } else {
                    $qty += $item->getQty();
                }
            }
            $this->setData('all_items_qty', $qty);
        }
        return $qty;
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        return [
            'grand_total' => $this->getGrandTotal(),
            'base_grandtotal' => $this->getBaseGrandTotal(),
            'subtotal' => $this->getSubtotal(),
            'base_subtotal' => $this->getBaseSubtotal(),
        ];
    }

    /**
     * @param $name
     * @param $message
     * @param $messageType
     * @param bool $isNotifyCustomer
     */
    public function addMessage($name, $message, $messageType, $isNotifyCustomer = false)
    {

    }

    /**
     * @return bool|array
     */
    public function getMessages()
    {
        if ($this->getMessagesCollection() !== false)
            return $this->getMessagesCollection()->toArray();
        return false;
    }

    /**
     * @return $this
     */
    protected function _afterLoad()
    {
        return parent::_afterLoad();
    }

    /**
     * reset
     */
    public function reset(){
        $this->_items = null;
        $this->_messages = null;
        $this->_customer = null;
        return $this;
    }
    /**
     * @param $inc
     * @return $this
     */
    public function loadByIncrementId($inc)
    {
        return $this->load($inc, 'increment_id');
    }

    /**
     * @return bool
     */
    public function canAccept()
    {
        return (bool) $this->getStatus() != self::STATUS_ACCEPTED;
    }

    /**
     * @return bool
     */
    public function canReject()
    {
        return (bool) $this->getStatus() != self::STATUS_REJECTED;
    }

    /**
     * customer send quote to admin
     * set increment ID and save
     */
    public function processing()
    {
        $this->setStatus(self::STATUS_PROCESSING);
        if(!$this->getIncrementId()) {
            $this->setIncrementId($this->helper->getIncrementNumber($this->getStoreId()));
        }
        $this->collectTotals();
        $this->save();
    }

    /**
     * @return $this
     */
    public function sendNewQuoteRequestEmail()
    {
        $email = $this->getCustomerEmail();
        $customer = $this->getCustomer();

        $store = $this->getStore();
        $dataVar = [
            "customer"=> $customer,
            "store"=>$store,
            'quote' => $this
        ];
        try {
            $this->emailHelper->sendTransactionEmail(
                '',
                \Magento\Framework\App\Area::AREA_FRONTEND,
                '',
                $email,
                $dataVar
            );
        } catch (\Exception $e) {

        }
        return $this;
    }
}
