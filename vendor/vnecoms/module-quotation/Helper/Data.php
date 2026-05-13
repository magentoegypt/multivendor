<?php

namespace Vnecoms\Quotation\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\ScopeInterface;
use Vnecoms\Quotation\Model\Quote;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_LOGIN_REQUIRED           = 'quotation/frontend/login_require';
    const XML_PATH_FORM_CONFIG_TELEPHONE    = 'quotation/form_config/telephone';
    const XML_PATH_FORM_CONFIG_COMPANY      = 'quotation/form_config/company';
    const XML_PATH_FORM_CONFIG_TAXID        = 'quotation/form_config/taxid';
    
    const XML_PATH_CATEGORY_CONTAINER_SELECTOR          = 'quotation/category_page/container_selector';
    const XML_PATH_CATEGORY_ADD_TO_CART_BTN_SELECTOR    = 'quotation/category_page/add_to_cart_btn_selector';

    const XML_PATH_EMAIL_IDENTITY                       = 'quotation/email/email_sender';
    const XML_PATH_EMAIL_CUSTOMER_NEW_QUOTE             = 'quotation/email/customer_new_quote';
    const XML_PATH_EMAIL_CUSTOMER_NEW_QUOTE_GUEST       = 'quotation/email/customer_new_quote_guest';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_APPROVED        = 'quotation/email/customer_quote_approved';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_APPROVED_GUEST  = 'quotation/email/customer_quote_approved_guest';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_CANCELLED       = 'quotation/email/customer_quote_cancelled';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_CANCELLED_GUEST = 'quotation/email/customer_quote_cancelled_guest';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_HELD            = 'quotation/email/customer_quote_held';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_HELD_GUEST      = 'quotation/email/customer_quote_held_guest';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_UNHELD          = 'quotation/email/customer_quote_unheld';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_UNHELD_GUEST    = 'quotation/email/customer_quote_unheld_guest';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_REJECTED        = 'quotation/email/customer_quote_rejected';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_REJECTED_GUEST  = 'quotation/email/customer_quote_rejected_guest';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_EXPIRED         = 'quotation/email/customer_quote_expired';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_EXPIRED_GUEST   = 'quotation/email/customer_quote_expired_guest';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_REMINDER        = 'quotation/email/customer_quote_reminder';
    const XML_PATH_EMAIL_CUSTOMER_QUOTE_MESSAGE         = 'quotation/email/customer_quote_message';
    
    const XML_PATH_EMAIL_ADMIN_EMAILS                   = 'quotation/email/admin_emails';
    const XML_PATH_EMAIL_ADMIN_NEW_QUOTE                = 'quotation/email/admin_new_quote';
    const XML_PATH_EMAIL_ADMIN_QUOTE_REJECTED           = 'quotation/email/admin_quote_rejected';
    const XML_PATH_EMAIL_ADMIN_QUOTE_ORDERED            = 'quotation/email/admin_quote_ordered';
    const XML_PATH_EMAIL_ADMIN_QUOTE_MESSAGE            = 'quotation/email/admin_quote_message';
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Convert\DataSize
     */
    protected $dataSize;

    /**
     * @param Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Convert\DataSize $dataSize
     */
    public function __construct
    (
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Convert\DataSize $dataSize
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_localeDate = $localeDate;
        $this->priceCurrency = $priceCurrency;
        $this->dataSize = $dataSize;
    }

    /**
     * @param $storeId
     * @return string
     */
    public function requireCustomerLogin($storeId='')
    {
        return $this->getConfig('quotation/frontend/login_require', $storeId);
    }

    /**
     * @param $configId
     * @param null|string|integer $store
     * @return string
     */
    public function getConfig($configId, $store = null)
    {
        if ($store === null) $store = $this->_storeManager->getStore()->getId();

        return $this->scopeConfig->getValue($configId, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $store);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getQuotePrefix($storeId='')
    {
        return $this->getConfig('quotation/format/prefix', $storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getCurrentNumber($storeId='')
    {
        return $this->getConfig('quotation/format/current_value', $storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getIncrementNumber($storeId='')
    {
        return $this->getConfig('quotation/format/increment', $storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getPadLength($storeId='')
    {
        return $this->getConfig('quotation/format/pad_length', $storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getExpirationTime($storeId='')
    {
        return $this->getConfig('quotation/time/expired', $storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getReminderTime($storeId='')
    {
        return $this->getConfig('quotation/time/reminder', $storeId);
    }

    /**
     * @param float $price
     * @param bool $format
     * @return float
     */
    public function convertPrice($price, $format = true)
    {
        return $format
            ? $this->priceCurrency->convertAndFormat($price)
            : $this->priceCurrency->convert($price);
    }

    /**
     * Get Max Size
     * 
     * @param string $storeId
     * @return number
     */
    public function getMaxSize($storeId = '')
    {
        $config = $this->getConfig('quotation/message/size', $storeId).'M'; //by MB
        return $this->dataSize->convertSizeToBytes($config);
    }

    /**
     * @param string $storeId
     * @return string
     */
    public function getAllowExtensions($storeId = '')
    {
        return $this->getConfig('quotation/message/allow_extensions', $storeId);
    }

    public function getMaxNumber($storeId = '')
    {
        return $this->getConfig('quotation/message/number', $storeId);
    }

    public function allowCustomerUpload($storeId = '')
    {
        return $this->getConfig('quotation/message/allow_customer_upload', $storeId);
    }

    /**
     * @param $type
     * @return string
     */
    public function getMimeType($type)
    {
        return mime_content_type($type);
    }

    /**
     * Is login required
     * 
     * @param int $storeId
     * @return boolean
     */
    public function isLoginRequired($storeId = null)
    {
        return (bool) $this->getConfig(self::XML_PATH_LOGIN_REQUIRED, $storeId);
    }
    
    /**
     * Get telephone form config
     * 
     * @param int $storeId
     * @return int
     */
    public function getTelephoneConfig($storeId = null){
        return $this->getConfig(self::XML_PATH_FORM_CONFIG_TELEPHONE, $storeId);
    }
    
    /**
     * Get company form config
     *
     * @param int $storeId
     * @return int
     */
    public function getCompanyConfig($storeId = null){
        return $this->getConfig(self::XML_PATH_FORM_CONFIG_COMPANY, $storeId);
    }
    
    /**
     * Get tax id form config
     *
     * @param int $storeId
     * @return int
     */
    public function getTaxIdConfig($storeId = null){
        return $this->getConfig(self::XML_PATH_FORM_CONFIG_TAXID, $storeId);
    }
    
    /**
     * Get Quotation Prefix.
     *
     * @param string $entityType
     * @param int    $storeId
     *
     * @return string
     */
    public function getPrefix($storeId)
    {
        return $this->scopeConfig->getValue(
            'quotation/format/prefix',
            'store',
            $storeId
        );
    }
    
    /**
     * Get Quotation Suffix.
     *
     * @param string $entityType
     * @param int    $storeId
     *
     * @return string
     */
    public function getSuffix($storeId)
    {
        return $this->scopeConfig->getValue(
            'quotation/format/suffix',
            'store',
            $storeId
        );
    }
    
    /**
     * Get Number Length.
     *
     * @param string $entityType
     * @param int    $storeId
     *
     * @return string
     */
    public function getNumberLength($storeId)
    {
        return $this->scopeConfig->getValue(
            'quotation/format/number_length',
            'store',
            $storeId
        );
    }
    
    /**
     * Get Increment Step.
     *
     * @param string $entityType
     * @param int    $storeId
     *
     * @return string
     */
    public function getStep($storeId)
    {
        return $this->scopeConfig->getValue(
            'quotation/format/step',
            'store',
            $storeId
        );
    }
    
    /**
     * Get Increment Step.
     *
     * @param string $entityType
     * @param int    $storeId
     *
     * @return string
     */
    public function getStartValue($storeId)
    {
        return $this->scopeConfig->getValue(
            'quotation/format/start_value',
            'store',
            $storeId
        );
    }
    
    /**
     * Get Increment Step.
     *
     * @param string $entityType
     * @param int    $storeId
     *
     * @return string
     */
    public function isSeparatedCounterForWebsite($storeId)
    {
        return $this->scopeConfig->getValue(
            'quotation/format/website_counter',
            'store',
            $storeId
        );
    }
    
    /**
     * Get Increment Step.
     *
     * @param string $entityType
     * @param int    $storeId
     *
     * @return string
     */
    public function isSeparatedCounterForStore($storeId)
    {
        return $this->scopeConfig->getValue(
            'quotation/format/store_counter',
            'store',
            $storeId
        );
    }

    /**
     * Get Admin Emails
     *
     * @return multitype:
     */
    public function getAdminEmails(){
        $emails = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_ADMIN_EMAILS);
        if (!$emails) return [];
        $emails = explode(",", $emails);

        return $emails;
    }
    
    /**
     * @return \Magento\Framework\App\Config\mixed
     */
    public function getCategoryContainerSelector(){
        return $this->scopeConfig->getValue(self::XML_PATH_CATEGORY_CONTAINER_SELECTOR);
    }
    
    /**
     * @return \Magento\Framework\App\Config\mixed
     */
    public function getCategoryAddToCartBtnSelector(){
        return $this->scopeConfig->getValue(self::XML_PATH_CATEGORY_ADD_TO_CART_BTN_SELECTOR);
    }
}