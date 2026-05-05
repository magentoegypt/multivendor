<?php
namespace MagentoEgypt\SmsExtend\Helper;

use Vnecoms\Sms\Helper\Data as VnecomsSmsHelperData;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Vnecoms\Sms\Model\Sms;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Vnecoms\Sms\Model\ResourceModel\Rule\CollectionFactory as BlockRuleCollectionFactory;

class SmsData extends VnecomsSmsHelperData
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Get current gateway
     *
     * @param string $storeId
     * @return string
     */
    public function getCurrentGateway($storeId = null){
        return $this->getConfigValue(self::XML_PATH_GATEWAY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get gateway Model
     *
     * @return boolean|\Vnecoms\Sms\Model\GatewayInterface
     */
    public function getGatewayModel($gateway = false){
        $gateway = $gateway?$gateway:$this->getCurrentGateway();
        if(!$gateway || !isset($this->smsGateways[$gateway])) return false;

        $gateway = $this->smsGateways[$gateway];
        if(!class_exists($gateway)) return false;

        return ObjectManager::getInstance()->create($gateway);
    }

    /**
     * Send the sms to a vendor
     *
     * @param string $number
     * @param string $message
     * @param string $additionalData
     */
    public function sendSms($number, $message, $additionalData = null){
        if(!$number) {
            return;
        }

        $gatewayModel = $this->getGatewayModel();
        if(!$gatewayModel){
            $this->_logger->error(__("Your sms gateway model is not valid"));
            return;
        }

        if(!$gatewayModel->validateConfig()){
            $this->_logger->error(__("Your sms gateway config is not valid or not setup correctly."));
            return;
        }
        $transport = new \Magento\Framework\DataObject([
            'can_send_sms'  => true,
            'number'        => $number,
            'message'       => $message,
            'additional_data'   => $additionalData,
        ]);
        $this->_eventManager->dispatch('vnecoms_sms_can_send',['transport' => $transport]);

        $result = [];
        /*Check if the mobile number is blocked*/
        $ruleCollection = $this->blockRuleCollectionFactory->create();
        foreach($ruleCollection as $rule){
            if($rule->isBlocked($number)){
                $transport->setData('can_send_sms', false);
                $result['note'] = __('The mobile number %1 is blocked by rule #%2', $number, $rule->getId());
                break;
            }
        }

        if($transport->getCanSendSms()){
            try{
                $result = $gatewayModel->sendSms($number, $message);
            }catch (\Exception $e){
                $this->_logger->error(__('Sms message is not sent: "%1"', $message));
                $this->_logger->error($e->getMessage());
                $result = ['status' => Sms::STATUS_FAILED];
            }
        }else{
            $result['status'] = $transport->getStatus();
        }
        /* Save the sent message to database*/
        $data = [
            'sid' => isset($result['sid'])?$result['sid']:'',
            'gateway' => $this->getCurrentGateway(),
            'message' => $message,
            'to_mobile' => $number,
            'additional_data' => $additionalData,
            'note' => isset($result['note'])?$result['note']:'',
            'status' => isset($result['status'])?$result['status']:'',
        ];
        $transport = new \Magento\Framework\DataObject(['sms_data' => $data]);
        $this->_eventManager->dispatch('vnecoms_sms_prepare_sms_data',['transport' => $transport]);

        $data = $transport->getSmsData();

        $sms = ObjectManager::getInstance()->create('Vnecoms\Sms\Model\Sms');
        $sms->setData($data)->save();
    }

    /**
     * Get admin telephone
     *
     * @param string $storeId
     * @return string
     */
    public function getAdminTelephone($storeId = null){
        return $this->getConfigValue(self::XML_PATH_ADMIN_TELEPHONE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Send a SMS to admin phone number
     *
     * @param string $message
     */
    public function sendAdminSms($message, $additionalData = null){
        if (!$this->getAdminTelephone()) return false;
        $number = trim($this->getAdminTelephone());
        $number = str_replace(" ", "", $number);
        if(!$number) return;
        $numbers = explode(",", $number);
        foreach($numbers as $number){
            $this->sendSms($number, $message, $additionalData);
        }
    }

    /**
     * Send customer SMS
     *
     * @param \Magento\Framework\DataObject $customer
     * @param string $message
     * @param string $additionalData
     */
    public function sendCustomerSms(
        \Magento\Framework\DataObject $customer,
        $message,
        $additionalData = null
    ) {
        /* Get customer mobile number*/
        $mobileNumber = $customer->getMobilenumber();

        if(!$mobileNumber) {
            return;
        }

        $this->sendSms($mobileNumber, $message, $additionalData);
    }

    /**
     * Get sms gateways
     *
     * @return array
     */
    public function getGateways(){
        return $this->smsGateways;
    }

    /**
     * is unique mobile number
     *
     * @param string $storeId
     * @return boolean
     */
    public function isUniqueMobileNumber($storeId = null){
        return !$this->getConfigValue(self::XML_PATH_DUPLICATED_MOBILE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * is Enabled verifying customer's mobile number
     *
     * @param string $storeId
     * @return boolean
     */
    public function isEnableVerifyingCustomerMobile($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_VERIFY_MOBILE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getConfigTypeLogin($storeId = null){
        return $this->getConfigValue(self::XML_PATH_ENABLE_OTP_LOGIN, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Is enabled Otp at login form
     *
     * @param null $storeId
     * @return bool
     */
    public function isEnabledOtpLogin($storeId = null){
        $config = $this->getConfigTypeLogin($storeId);
        return $config == \Vnecoms\Sms\Model\Config\Source\LoginType::TYPE_OTP;
    }

    /**
     * Is enabled Otp at login form
     *
     * @param null $storeId
     * @return bool
     */
    public function isEnabled2FALogin($storeId = null){
        $config = $this->getConfigTypeLogin($storeId);
        return $config == \Vnecoms\Sms\Model\Config\Source\LoginType::TYPE_2FA;
    }


    /**
     * is Enabled verifying customer's mobile number on registration form
     *
     * @param string $storeId
     * @return boolean
     */
    public function isEnableVerifyingOnRegister($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_VERIFY_MOBILE_ON_REGISTER, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * is Enabled verifying customer's address mobile number
     *
     * @param string $storeId
     * @return boolean
     */
    public function isEnableVerifyingAddressMobile($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_VERIFY_MOBILE_ON_ADDRESS, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Otp Format
     *
     * @param string $storeId
     * @return string
     */
    public function getOtpFormat($storeId = null){
        return $this->getConfigValue(self::XML_PATH_OTP_FORMAT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Otp length
     *
     * @param string $storeId
     * @return number
     */
    public function getOtpLength($storeId = null){
        return (int)$this->getConfigValue(self::XML_PATH_OTP_LENGTH, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Otp expired period time
     *
     * @param string $storeId
     * @return number
     */
    public function getOtpExpiredPeriodTime($storeId = null){
        return (int)$this->getConfigValue(self::XML_PATH_OTP_EXPIRED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Otp resend period time
     *
     * @param string $storeId
     * @return number
     */
    public function getOtpResendPeriodTime($storeId = null){
        return (int)$this->getConfigValue(self::XML_PATH_OTP_RESEND, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get max number of times customer resends OTP
     *
     * @param string $storeId
     * @return number
     */
    public function getOtpMaxResendingTimes($storeId = null){
        return (int)$this->getConfigValue(self::XML_PATH_OTP_MAX_RESENDING_TIMES, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get period time of block sending OTP
     *
     * @param string $storeId
     * @return number
     */
    public function getOtpResendBlockTime($storeId = null){
        return (int)$this->getConfigValue(self::XML_PATH_OTP_RESEND_BLOCK_TIME, ScopeInterface::SCOPE_STORE, $storeId);
    }



    /**
     * Get Otp Message
     *
     * @param string $storeId
     * @return string
     */
    public function getOtpMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_OTP_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Charset
     *
     * @return string:
     */
    public function getCharset()
    {
        return str_split($this->otpParameters['charset'][$this->getOtpFormat()]);
    }



    /**
     * Can send customer register sms message to admin
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendCustomerRegisterMessageToAdmin($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_ADMIN_CUSTOMER_REGISTER, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get customer register sms message template sent to admin
     *
     * @param string $storeId
     * @return string
     */
    public function getCustomerRegisterMessageSendToAdmin($storeId = null){
        return $this->getConfigValue(self::XML_PATH_ADMIN_CUSTOMER_REGISTER_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send new order sms notification message to admin
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendNewOrderMessageToAdmin($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_ADMIN_NEW_ORDER, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new order sms message template sent to admin
     *
     * @param string $storeId
     * @return string
     */
    public function getNewOrderMessageSendToAdmin($storeId = null){
        return $this->getConfigValue(self::XML_PATH_ADMIN_NEW_ORDER_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send new contact sms notification message to admin
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendNewContactMessageToAdmin($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_ADMIN_NEW_CONTACT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new contact sms message template sent to admin
     *
     * @param string $storeId
     * @return string
     */
    public function getNewContactMessageSendToAdmin($storeId = null){
        return $this->getConfigValue(self::XML_PATH_ADMIN_NEW_CONTACT_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send new contact sms notification message to admin
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendNewReviewMessageToAdmin($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_ADMIN_NEW_REVIEW, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new contact sms message template sent to admin
     *
     * @param string $storeId
     * @return string
     */
    public function getNewReviewMessageSendToAdmin($storeId = null){
        return $this->getConfigValue(self::XML_PATH_ADMIN_NEW_REVIEW_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send customer register sms message to customer
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendCustomerRegisterMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_CUSTOMER_REGISTER, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get customer register sms message template
     *
     * @param string $storeId
     * @return string
     */
    public function getCustomerRegisterMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_REGISTER_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send new order sms notification message to customer
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendNewOrderMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_ORDER, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new order sms message template sent to customer
     *
     * @param string $storeId
     * @return string
     */
    public function getNewOrderMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_ORDER_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new order sms message templates sent to customer by payment method
     *
     * @param string $storeId
     * @return string
     */
    public function getNewOrderMessagesByPayment($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_ORDER_MESSAGE_BY_PAYMENT_METHOD, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new order sms message templates sent to customer by payment method
     *
     * @param string $storeId
     * @return string
     */
    public function getNewOrderMessagesByShipping($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_ORDER_MESSAGE_BY_SHIPPING_METHOD, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new order sms message templates sent to customer by payment method
     *
     * @param string $storeId
     * @return string
     */
    public function getNewInvoiceMessagesByPayment($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_INVOICE_MESSAGE_BY_PAYMENT_METHOD, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new order sms message templates sent to customer by payment method
     *
     * @param string $storeId
     * @return string
     */
    public function getNewInvoiceMessagesByShipping($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_INVOICE_MESSAGE_BY_SHIPPING_METHOD, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send order status changed sms notification message to customer
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendOrderStatusChangedMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_CUSTOMER_ORDER_STATUS_CHANGED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get order status changed sms message template sent to customer
     *
     * @param string $storeId
     * @return string
     */
    public function getOrderStatusChangedMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_ORDER_STATUS_CHANGED_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send new invoice message to customer
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendNewInvoiceMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_INVOICE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new invoice message template sent to customer
     *
     * @param string $storeId
     * @return string
     */
    public function getNewInvoiceMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_INVOICE_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send new shipment message to customer
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendNewShipmentMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_SHIPMENT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new shipment sms message templates sent to customer by shipping method
     *
     * @param string $storeId
     * @return string
     */
    public function getNewShipmentMessagesByShippingMethod($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_SHIPMENT_MESSAGE_BY_SHIPPING_METHOD, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new shipment sms message templates sent to customer by shipping method
     *
     * @param string $storeId
     * @return string
     */
    public function getNewShipmentMessagesByPayment($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_SHIPMENT_MESSAGE_BY_PAYMENT_METHOD, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new shipment sms message templates sent to customer by shipping method
     *
     * @param string $storeId
     * @return string
     */
    public function getNewCreditMemoMessagesByShipping($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_CREDITMEMO_MESSAGE_BY_SHIPPING_METHOD, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new shipment sms message templates sent to customer by shipping method
     *
     * @param string $storeId
     * @return string
     */
    public function getNewCreditMemoMessagesByPayment($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_CREDITMEMO_MESSAGE_BY_PAYMENT_METHOD, ScopeInterface::SCOPE_STORE, $storeId);
    }


    /**
     * Get new shipment message template sent to customer
     *
     * @param string $storeId
     * @return string
     */
    public function getNewShipmentMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_SHIPMENT_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send new credit memo message to customer
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendNewCreditmemoMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_CREDITMEMO, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get new credit memo message template sent to customer
     *
     * @param string $storeId
     * @return string
     */
    public function getNewCreditmemoMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_NEW_CREDITMEMO_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get allow country dropdown
     *
     * @param string $storeId
     * @return string
     */
    public function getAllowCountryDropdown($storeId = null){
        $allowDropdown = $this->getConfigValue(self::XML_PATH_INPUT_SETTING_ALLOW_COUNTRY_DROPDOWN, ScopeInterface::SCOPE_STORE, $storeId);
				return $allowDropdown?'true':'false';
    }

    /**
     * Get default country
     *
     * @param string $storeId
     * @return string
     */
    public function getInitialCountry($storeId = null){
        return $this->getConfigValue(self::XML_PATH_INPUT_SETTING_DEFAULT_COUNTRY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get GeoIP database path
     *
     * @param string $storeId
     * @return string
     */
    public function getGeoIpDatabase($storeId = null){
        return $this->getConfigValue(self::XML_PATH_INPUT_SETTING_GEOIP_DATABASE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * is allowed all countries
     *
     * @param string $storeId
     * @return boolean
     */
    public function isAllowedAllCountries($storeId = null){
        return !$this->getConfigValue(self::XML_PATH_INPUT_SETTING_ALLOW_SPECIFIC, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get allowed countries
     *
     * @param string $storeId
     * @return string
     */
    public function getAllowedCountries($storeId = null){
        return $this->getConfigValue(self::XML_PATH_INPUT_SETTING_SPECIFIC_COUNTRY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get preferred countries
     *
     * @param string $storeId
     * @return string
     */
    public function getPreferredCountries($storeId = null){
        return $this->getConfigValue(self::XML_PATH_INPUT_SETTING_PREFERRED_COUNTRY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Is Enabled Mobile Login
     *
     * @param string $storeId
     * @return boolean
     */
    public function isEnableMobileLogin($storeId = null){
        return $this->getConfigValue(self::XML_PATH_MOBILE_LOGIN, ScopeInterface::SCOPE_STORE, $storeId) &&
        $this->isUniqueMobileNumber();
    }

    /**
     * Is Enabled Mobile Forgot Password
     *
     * @param sring $storeId
     * @return bool
     */
    public function isEnableMobileForgotPassword($storeId = null){
        return $this->getConfigValue(self::XML_PATH_MOBILE_LOGIN, ScopeInterface::SCOPE_STORE, $storeId) &&
            $this->isUniqueMobileNumber();
    }

    /**
     * Get Mobile Source
     *
     * @param int $storeId
     * @return string
     */
    public function getMobileSources($storeId = null){
        $customerPriority = $this->getConfigValue(self::XML_PATH_CUSTOMER_MOBILE_SOURCE_CUSTOMER, ScopeInterface::SCOPE_STORE, $storeId);
        $billingPriority = $this->getConfigValue(self::XML_PATH_CUSTOMER_MOBILE_SOURCE_BILLING, ScopeInterface::SCOPE_STORE, $storeId);
        $shippingPriority = $this->getConfigValue(self::XML_PATH_CUSTOMER_MOBILE_SOURCE_SHIPPING, ScopeInterface::SCOPE_STORE, $storeId);
        $sources = [
            $customerPriority => self::MOBILE_SOURCE_CUSTOMER,
            $billingPriority => self::MOBILE_SOURCE_BILLING,
            $shippingPriority => self::MOBILE_SOURCE_SHIPPING,
        ];

        ksort($sources);

        return $sources;
    }

    /**
     * Get Customer object for sending SMS
     *
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Framework\DataObject
     */
    public function getCustomerObjectForSendingSms(\Magento\Sales\Model\Order $order){
        $mobile = '';
        $customer = null;

        $isUseBillingInfo = false;
        $method = $order->getShippingMethod();
        $method = explode("_", $method);
        if(in_array($method[0] ,$this->shippingMethodUseBilling)) {
            $isUseBillingInfo = true;
        }

        foreach($this->getMobileSources($order->getStoreId()) as $source){
            if($mobile) break;
            switch($source){
                case self::MOBILE_SOURCE_CUSTOMER:
                    if($order->getCustomerId()){
                        $customer = $this->customerFactory->create()
                            ->load($order->getCustomerId());
                        $mobile = $customer->getMobilenumber();
                    }
                    break;
                case self::MOBILE_SOURCE_BILLING:
                    $mobile = $order->getBillingAddress()->getTelephone();
                    break;
                case self::MOBILE_SOURCE_SHIPPING:
                    if(!$order->getIsVirtual() && !$isUseBillingInfo){
                        $mobile = $order->getShippingAddress()->getTelephone();
                    } else {
                        $mobile = $order->getBillingAddress()->getTelephone();
                    }
                    break;
            }
        }

        if($order->getCustomerId()){
            if(!$customer) $customer = $this->customerFactory->create()
                ->load($order->getCustomerId());
        }else{
            $customer = $order->getIsVirtual() || $isUseBillingInfo ? $order->getBillingAddress() : $order->getShippingAddress();
        }

        $customer->setMobilenumber($mobile);
        return $customer;
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
