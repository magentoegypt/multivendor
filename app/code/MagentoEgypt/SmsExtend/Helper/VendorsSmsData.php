<?php
namespace MagentoEgypt\SmsExtend\Helper;

use Vnecoms\VendorsSms\Helper\Data as VnecomsSmsHelperData;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Vnecoms\VendorsConfig\Helper\Data as ConfigHelper;
use Vnecoms\Sms\Helper\Data as SmsHelper;
use GuzzleHttp\json_decode;
use Magento\Framework\App\ObjectManager;
use Vnecoms\Sms\Model\Sms;
use Magento\Store\Model\ScopeInterface;

class VendorsSmsData extends VnecomsSmsHelperData
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
        $tirePrice = $this->getConfigValue(self::XML_PATH_SMS_TIER_PRICE);
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
        if (!$this->getConfigValue(self::XML_PATH_SMS_CREDIT_PACKAGES)) return [];
        $packages = unserialize($this->getConfigValue(self::XML_PATH_SMS_CREDIT_PACKAGES));
        if(!$packages) return [];
        return $packages;
    }

    /**
     * Can send vendor approved message
     *
     * @return boolean
     */
    public function canSendVendorApprovedMessage(){
        return (bool)$this->getConfigValue(self::XML_PATH_VENDOR_APPROVED);
    }

    /**
     * Get vendor approved message
     *
     * @return string
     */
    public function getVendorApprovedMessage(){
        return $this->getConfigValue(self::XML_PATH_VENDOR_APPROVED_MESSAGE);
    }

    /**
     * Can send vendor unapproved message
     *
     * @return boolean
     */
    public function canSendVendorUnapprovedMessage(){
        return (bool)$this->getConfigValue(self::XML_PATH_VENDOR_UNAPPROVED);
    }

    /**
     * Get vendor unapproved message
     *
     * @return string
     */
    public function getVendorUnapprovedMessage(){
        return $this->getConfigValue(self::XML_PATH_VENDOR_UNAPPROVED_MESSAGE);
    }

    /**
     * Can send product approved message
     * @param int $vendorId
     * @return boolean
     */
    public function canSendProductApprovedMessage($vendorId){
        return (bool)$this->getConfigValue(self::XML_PATH_PRODUCT_APPROVED) &&
            (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_PRODUCT_APPROVED, $vendorId);
    }

    /**
     * Get product approved message
     *
     * @return string
     */
    public function getProductApprovedMessage(){
        return $this->getConfigValue(self::XML_PATH_PRODUCT_APPROVED_MESSAGE);
    }

    /**
     * Can send product unapproved message
     *
     * @param int $vendorId
     * @return boolean
     */
    public function canSendProductUnapprovedMessage($vendorId){
        return (bool)$this->getConfigValue(self::XML_PATH_PRODUCT_UNAPPROVED) &&
            (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_PRODUCT_UNAPPROVED, $vendorId);;
    }

    /**
     * Get product unapproved message
     *
     * @return string
     */
    public function getProductUnapprovedMessage(){
        return $this->getConfigValue(self::XML_PATH_PRODUCT_UNAPPROVED_MESSAGE);
    }

    /**
     * Can send new order message
     *
     * @param int $vendorId
     * @return boolean
     */
    public function canSendNewOrderMessage($vendorId){
        return (bool)$this->getConfigValue(self::XML_PATH_NEW_ORDER) &&
        (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_NEW_ORDER, $vendorId);
    }

    /**
     * Get new order message
     *
     * @return string
     */
    public function getNewOrderMessage(){
        return $this->getConfigValue(self::XML_PATH_NEW_ORDER_MESSAGE);
    }

    /**
     * Can send new invoice message
     * @param int $vendorId
     * @return boolean
     */
    public function canSendNewInvoiceMessage($vendorId){
        return (bool)$this->getConfigValue(self::XML_PATH_NEW_INVOICE) &&
        (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_NEW_INVOICE, $vendorId);
    }

    /**
     * Get new invoice message
     *
     * @return string
     */
    public function getNewInvoiceMessage(){
        return $this->getConfigValue(self::XML_PATH_NEW_INVOICE_MESSAGE);
    }

    /**
     * Can send new shipment message
     *
     * @param int $vendorId
     * @return boolean
     */
    public function canSendNewShipmentMessage($vendorId){
        return (bool)$this->getConfigValue(self::XML_PATH_NEW_SHIPMENT) &&
        (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_NEW_SHIPMENT, $vendorId);
    }

    /**
     * Get new shipment message
     *
     * @return string
     */
    public function getNewShipmentMessage(){
        return $this->getConfigValue(self::XML_PATH_NEW_SHIPMENT_MESSAGE);
    }

    /**
     * Can send new creditmemo message
     *
     * @param int $vendorId
     * @return boolean
     */
    public function canSendNewCreditmemoMessage($vendorId){
        return (bool)$this->getConfigValue(self::XML_PATH_NEW_CREDITMEMO) &&
        (bool)$this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_NEW_CREDITMEMO, $vendorId);
    }

    /**
     * Get new shipment message
     *
     * @return string
     */
    public function getNewCreditmemoMessage(){
        return $this->getConfigValue(self::XML_PATH_NEW_CREDITMEMO_MESSAGE);
    }


    /**
     * Can send vendor approved message
     *
     * @return boolean
     */
    public function canSendToAdminPendingVendorMessage(){
        return (bool)$this->getConfigValue(self::XML_PATH_ADMIN_PENDING_VENDOR);
    }

    /**
     * Get vendor approved message
     *
     * @return string
     */
    public function getAdminPendingVendorMessage(){
        return $this->getConfigValue(self::XML_PATH_ADMIN_PENDING_VENDOR_MESSAGE);
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

    protected function getConfigValue($path, $scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        if ($storeId === null) {
            if($this->storeManager === null) {
                $this->storeManager = ObjectManager::getInstance()->get('Magento\Store\Model\StoreManagerInterface');
            }
            $storeId = $this->storeManager->getStore()->getId();
        }
        return $this->scopeConfig->getValue($path, $scope, $storeId);
    }
}
