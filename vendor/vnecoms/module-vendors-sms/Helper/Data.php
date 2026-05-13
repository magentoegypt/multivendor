<?php
namespace Vnecoms\VendorsSms\Helper;

use Magento\Framework\App\Helper\Context;
use Vnecoms\VendorsConfig\Helper\Data as ConfigHelper;
use Vnecoms\Sms\Helper\Data as SmsHelper;
use GuzzleHttp\json_decode;
use Magento\Framework\App\ObjectManager;
use Vnecoms\Sms\Model\Sms;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_SMS_TIER_PRICE               = 'vsms/settings/sms_tier_price';
    const XML_PATH_SMS_CREDIT_PACKAGES          = 'vsms/settings/sms_credit_packages';
    const XML_PATH_VENDOR_APPROVED              = 'vsms/vendor/vendor_approved';
    const XML_PATH_VENDOR_APPROVED_MESSAGE      = 'vsms/vendor/vendor_approved_message';
    const XML_PATH_VENDOR_UNAPPROVED            = 'vsms/vendor/vendor_unapproved';
    const XML_PATH_VENDOR_UNAPPROVED_MESSAGE    = 'vsms/vendor/vendor_unapproved_message';

    const XML_PATH_PRODUCT_APPROVED             = 'vsms/vendor/product_approved';
    const XML_PATH_PRODUCT_APPROVED_MESSAGE     = 'vsms/vendor/product_approved_message';
    const XML_PATH_PRODUCT_UNAPPROVED           = 'vsms/vendor/product_unapproved';
    const XML_PATH_PRODUCT_UNAPPROVED_MESSAGE   = 'vsms/vendor/product_unapproved_message';

    const XML_PATH_NEW_ORDER                = 'vsms/vendor/new_order';
    const XML_PATH_NEW_ORDER_MESSAGE        = 'vsms/vendor/new_order_message';
    const XML_PATH_NEW_INVOICE              = 'vsms/vendor/new_invoice';
    const XML_PATH_NEW_INVOICE_MESSAGE      = 'vsms/vendor/new_invoice_message';
    const XML_PATH_NEW_SHIPMENT             = 'vsms/vendor/new_shipment';
    const XML_PATH_NEW_SHIPMENT_MESSAGE     = 'vsms/vendor/new_shipment_message';
    const XML_PATH_NEW_CREDITMEMO           = 'vsms/vendor/new_creditmemo';
    const XML_PATH_NEW_CREDITMEMO_MESSAGE   = 'vsms/vendor/new_creditmemo_message';

    const XML_PATH_VENDOR_MOBILE                    = 'sms_notification/general/mobile';
    const XML_PATH_VENDOR_PRODUCT_APPROVED          = 'sms_notification/configuration/product_approved';
    const XML_PATH_VENDOR_PRODUCT_UNAPPROVED        = 'sms_notification/configuration/product_unapproved';
    const XML_PATH_VENDOR_NEW_ORDER                 = 'sms_notification/configuration/new_order';
    const XML_PATH_VENDOR_NEW_INVOICE               = 'sms_notification/configuration/new_invoice';
    const XML_PATH_VENDOR_NEW_SHIPMENT              = 'sms_notification/configuration/new_shipment';
    const XML_PATH_VENDOR_NEW_CREDITMEMO            = 'sms_notification/configuration/new_creditmemo';


    const XML_PATH_ADMIN_PENDING_VENDOR             = 'vsms/vendor/admin_pending_vendor';
    const XML_PATH_ADMIN_PENDING_VENDOR_MESSAGE     = 'vsms/vendor/admin_pending_vendor_message';
    const XML_PATH_ADMIN_PENDING_PRODUCT            = 'vsms/vendor/admin_pending_product';
    const XML_PATH_ADMIN_PENDING_PRODUCT_MESSAGE    = 'vsms/vendor/admin_pending_product_message';

    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $configHelper;

    /**
     * @var \Vnecoms\Sms\Helper\Data
     */
    protected $smsHelper;

    /**
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param SmsHelper $smsHelper
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        SmsHelper $smsHelper
    ) {
        $this->configHelper = $configHelper;
        $this->smsHelper = $smsHelper;
        parent::__construct($context);
    }

    /**
     * Send the sms to a vendor
     *
     * @param \Vnecoms\Vendors\Model\Vendor $vendor
     * @param string $message
     */
    public function sendSms(\Vnecoms\Vendors\Model\Vendor $vendor, $message, $additionalData = null){
        $number = $this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_MOBILE, $vendor->getId());
        $vendorInfo = 'vendor|'.$vendor->getId();
        $additionalData = $additionalData === null?$vendorInfo:$vendorInfo.'||'.$additionalData;
        return $this->smsHelper->sendSms($number, $message, $additionalData);
    }

    /**
     * Send a SMS to admin phone number
     *
     * @param string $message
     * @param string $additionalData
     */
    public function sendAdminSms($message, $additionalData = null){
        return $this->smsHelper->sendAdminSms($message, $additionalData);
    }

    /**
     * Get Sms Tier Price
     *
     * @return array
     */
    public function getSmsTierPrice(){
        $tirePrice = $this->scopeConfig->getValue(self::XML_PATH_SMS_TIER_PRICE);
        if (!$tirePrice) return [];

        $tier = unserialize($tirePrice);
        if(!$tier) return [];

        return $tier;
    }

    /**
     * Get Sms Credit Packages
     *
     * @return array
     */
    public function getSmsCreditPackages(){
        if (!$this->scopeConfig->getValue(self::XML_PATH_SMS_CREDIT_PACKAGES)) return [];
        $packages = unserialize($this->scopeConfig->getValue(self::XML_PATH_SMS_CREDIT_PACKAGES));
        if(!$packages) return [];
        return $packages;
    }

    /**
     * Can send vendor approved message
     *
     * @return boolean
     */
    public function canSendVendorApprovedMessage(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_VENDOR_APPROVED);
    }

    /**
     * Get vendor approved message
     *
     * @return string
     */
    public function getVendorApprovedMessage(){
        return $this->scopeConfig->getValue(self::XML_PATH_VENDOR_APPROVED_MESSAGE);
    }

    /**
     * Can send vendor unapproved message
     *
     * @return boolean
     */
    public function canSendVendorUnapprovedMessage(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_VENDOR_UNAPPROVED);
    }

    /**
     * Get vendor unapproved message
     *
     * @return string
     */
    public function getVendorUnapprovedMessage(){
        return $this->scopeConfig->getValue(self::XML_PATH_VENDOR_UNAPPROVED_MESSAGE);
    }

    /**
     * Can send product approved message
     * @param int $vendorId
     * @return boolean
     */
    public function canSendProductApprovedMessage($vendorId){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_PRODUCT_APPROVED) &&
            (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_PRODUCT_APPROVED, $vendorId);
    }

    /**
     * Get product approved message
     *
     * @return string
     */
    public function getProductApprovedMessage(){
        return $this->scopeConfig->getValue(self::XML_PATH_PRODUCT_APPROVED_MESSAGE);
    }

    /**
     * Can send product unapproved message
     *
     * @param int $vendorId
     * @return boolean
     */
    public function canSendProductUnapprovedMessage($vendorId){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_PRODUCT_UNAPPROVED) &&
            (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_PRODUCT_UNAPPROVED, $vendorId);;
    }

    /**
     * Get product unapproved message
     *
     * @return string
     */
    public function getProductUnapprovedMessage(){
        return $this->scopeConfig->getValue(self::XML_PATH_PRODUCT_UNAPPROVED_MESSAGE);
    }

    /**
     * Can send new order message
     *
     * @param int $vendorId
     * @return boolean
     */
    public function canSendNewOrderMessage($vendorId){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_NEW_ORDER) &&
        (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_NEW_ORDER, $vendorId);
    }

    /**
     * Get new order message
     *
     * @return string
     */
    public function getNewOrderMessage(){
        return $this->scopeConfig->getValue(self::XML_PATH_NEW_ORDER_MESSAGE);
    }

    /**
     * Can send new invoice message
     * @param int $vendorId
     * @return boolean
     */
    public function canSendNewInvoiceMessage($vendorId){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_NEW_INVOICE) &&
        (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_NEW_INVOICE, $vendorId);
    }

    /**
     * Get new invoice message
     *
     * @return string
     */
    public function getNewInvoiceMessage(){
        return $this->scopeConfig->getValue(self::XML_PATH_NEW_INVOICE_MESSAGE);
    }

    /**
     * Can send new shipment message
     *
     * @param int $vendorId
     * @return boolean
     */
    public function canSendNewShipmentMessage($vendorId){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_NEW_SHIPMENT) &&
        (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_NEW_SHIPMENT, $vendorId);
    }

    /**
     * Get new shipment message
     *
     * @return string
     */
    public function getNewShipmentMessage(){
        return $this->scopeConfig->getValue(self::XML_PATH_NEW_SHIPMENT_MESSAGE);
    }

    /**
     * Can send new creditmemo message
     *
     * @param int $vendorId
     * @return boolean
     */
    public function canSendNewCreditmemoMessage($vendorId){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_NEW_CREDITMEMO) &&
        (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_NEW_CREDITMEMO, $vendorId);
    }

    /**
     * Get new shipment message
     *
     * @return string
     */
    public function getNewCreditmemoMessage(){
        return $this->scopeConfig->getValue(self::XML_PATH_NEW_CREDITMEMO_MESSAGE);
    }


    /**
     * Can send vendor approved message
     *
     * @return boolean
     */
    public function canSendToAdminPendingVendorMessage(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ADMIN_PENDING_VENDOR);
    }

    /**
     * Get vendor approved message
     *
     * @return string
     */
    public function getAdminPendingVendorMessage(){
        return $this->scopeConfig->getValue(self::XML_PATH_ADMIN_PENDING_VENDOR_MESSAGE);
    }

    /**
     * Count number of vendor sms from the date
     *
     * @param int $vendorId
     * @param string $date
     * @return number
     */
    public function countVendorSms($vendorId, $date){
        $resourceSms = ObjectManager::getInstance()
            ->create('Vnecoms\Sms\Model\ResourceModel\Sms');

        $connection = $resourceSms->getConnection();
        $select = $connection->select();
        $select->from(
            $resourceSms->getTable('ves_sms_message'),
            ['total_messages' => 'count(message_id)']
        )->where(
            'vendor_id = :vendor_id'
        )->where(
            'created_at > :date'
        )->where(
            'status in (?)',
            [
                Sms::STATUS_DELIVERED,
                Sms::STATUS_SENT,
                Sms::STATUS_PENDING,
            ]
        );
        $bind = [
            'vendor_id' => $vendorId,
            'date' => $date,
        ];
        $numberOfSms = $connection->fetchOne($select, $bind);

        return (int)$numberOfSms;
    }

    /**
     * Get charged credit from a given date
     *
     * @param int $vendorId
     * @param string $date
     * @return float
     */
    public function getChargedCredit($vendorId, $date){
        $resourceSms = ObjectManager::getInstance()
            ->create('Vnecoms\Sms\Model\ResourceModel\Sms');

        $connection = $resourceSms->getConnection();
        $select = $connection->select();
        $select->from(
            $resourceSms->getTable('ves_sms_message'),
            ['total_price' => 'sum(price)']
        )->where(
            'vendor_id = :vendor_id'
        )->where(
            'created_at > :date'
        )->where(
            'status in (?)',
            [
                Sms::STATUS_DELIVERED,
                Sms::STATUS_SENT,
                Sms::STATUS_PENDING,
            ]
        );
        $bind = [
            'vendor_id' => $vendorId,
            'date' => $date,
        ];
        $totalChargedCredit = $connection->fetchOne($select, $bind);

        return (float)$totalChargedCredit;
    }
}
