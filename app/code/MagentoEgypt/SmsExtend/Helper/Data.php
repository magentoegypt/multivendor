<?php
namespace MagentoEgypt\SmsExtend\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Vnecoms\Sms\Helper\Data as SmsHelper;
use Vnecoms\VendorsSms\Helper\Data as VendorSmsHelper;
use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ACCOUNT  = 'vsms/settings/smsextend_account';
    const XML_PATH_VERSION = 'vsms/settings/smsextend_version';
    const XML_PATH_TOKEN   = 'vsms/settings/smsextend_token';
    const XML_PATH_LANG   = 'vsms/settings/language';

    const XML_PATH_CUSTOMER_DELETE   = 'vsms/customer/customer_delete_enabled';
    const XML_PATH_CUSTOMER_DELETE_MESSAGE   = 'vsms/customer/customer_delete_message';
    const XML_PATH_CUSTOMER_EDIT   = 'vsms/customer/customer_edit_enabled';
    const XML_PATH_CUSTOMER_EDIT_MESSAGE   = 'vsms/customer/customer_edit_message';

    const XML_PATH_VENDOR_REGISTER              = 'vsms/vendor/vendor_register';
    const XML_PATH_VENDOR_REGISTER_MESSAGE      = 'vsms/vendor/vendor_register_message';
    const XML_PATH_VENDOR_EDIT              = 'vsms/vendor/vendor_edit';
    const XML_PATH_VENDOR_EDIT_MESSAGE      = 'vsms/vendor/vendor_edit_message';
    const XML_PATH_VENDOR_DELETE              = 'vsms/vendor/vendor_delete';
    const XML_PATH_VENDOR_DELETE_MESSAGE      = 'vsms/vendor/vendor_delete_message';

    public $template;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Encryption\Encryptor $crypt
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\Encryptor $crypt
    ) {
        $this->crypt = $crypt;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Can send customer delete sms message to customer
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendCustomerDeleteMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_CUSTOMER_DELETE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get customer delete sms message template
     *
     * @param string $storeId
     * @return string
     */
    public function getCustomerDeleteMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_DELETE_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send customer edit sms message to customer
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendCustomerEditMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_CUSTOMER_EDIT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get customer edit sms message template
     *
     * @param string $storeId
     * @return string
     */
    public function getCustomerEditMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_CUSTOMER_EDIT_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send new vendor sms message to vendor
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendVendorRegisterMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_VENDOR_REGISTER, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get vendor sms message template
     *
     * @param string $storeId
     * @return string
     */
    public function getVendorRegisterMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_VENDOR_REGISTER_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send vendor edit sms message to vendor
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendVendorEditMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_VENDOR_EDIT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get vendor edit sms message template
     *
     * @param string $storeId
     * @return string
     */
    public function getVendorEditMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_VENDOR_EDIT_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Can send vendor delet sms message to vendor
     *
     * @param string $storeId
     * @return boolean
     */
    public function canSendVendorDeleteMessage($storeId = null){
        return (bool)$this->getConfigValue(self::XML_PATH_VENDOR_DELETE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get vendor delete sms message template
     *
     * @param string $storeId
     * @return string
     */
    public function getVendorDeleteMessage($storeId = null){
        return $this->getConfigValue(self::XML_PATH_VENDOR_DELETE_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return string
     */
    public function getAccount($storeId = null){
        return $this->getConfigValue(self::XML_PATH_ACCOUNT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return string
     */
    public function getVersion($storeId = null){
        return $this->getConfigValue(self::XML_PATH_VERSION, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return string
     */
    public function getLang($storeId = null){
        return $this->getConfigValue(self::XML_PATH_LANG, ScopeInterface::SCOPE_STORE, $storeId) ?? 'en';
    }

    /**
     * @return string
     */
    public function getToken($storeId = null){
        return $this->getConfigValue(self::XML_PATH_TOKEN, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isOtpType($storeId = null) {
        return ($this->template == $this->getConfigValue(SmsHelper::XML_PATH_OTP_MESSAGE, ScopeInterface::SCOPE_STORE, $storeId));
    }

    public function getVars($message)
    {
        $template = $this->template;

        // Extract placeholders from the template
        preg_match_all('/{{([^}]*)}}/', $template, $placeholders);

        // Create a regex pattern by converting placeholders into capture groups
        // $pattern = preg_quote($template, '/');  // Escape special characters
        $pattern = preg_replace('/{{[^}]*}}/', '(.*?)', $template); // Replace placeholders with regex groups

        // Extract values from the message
        preg_match('/^' . $pattern . '$/', $message, $matches);
        // echo '<pre>';var_dump($placeholders, $template, $message, $pattern, $matches);die('</pre>');

        // Remove the first element (full match) from $matches
        array_shift($matches);

        // Map placeholders to their corresponding values
        $result = array_combine($placeholders[1], $matches);

        return $result;
    }

    public function getTemplateName(){
        $msgList = [
            SmsHelper::XML_PATH_OTP_MESSAGE,
            SmsHelper::XML_PATH_ADMIN_CUSTOMER_REGISTER_MESSAGE,
            SmsHelper::XML_PATH_ADMIN_NEW_CONTACT_MESSAGE,
            SmsHelper::XML_PATH_ADMIN_NEW_REVIEW_MESSAGE,
            SmsHelper::XML_PATH_CUSTOMER_REGISTER_MESSAGE,
            SmsHelper::XML_PATH_CUSTOMER_NEW_ORDER_MESSAGE,
            SmsHelper::XML_PATH_CUSTOMER_NEW_INVOICE_MESSAGE,
            SmsHelper::XML_PATH_CUSTOMER_NEW_SHIPMENT_MESSAGE,
            SmsHelper::XML_PATH_CUSTOMER_NEW_CREDITMEMO_MESSAGE,
            VendorSmsHelper::XML_PATH_VENDOR_APPROVED_MESSAGE,
            VendorSmsHelper::XML_PATH_VENDOR_UNAPPROVED_MESSAGE,
            VendorSmsHelper::XML_PATH_PRODUCT_APPROVED_MESSAGE,
            VendorSmsHelper::XML_PATH_PRODUCT_UNAPPROVED_MESSAGE,
            VendorSmsHelper::XML_PATH_NEW_ORDER_MESSAGE,
            VendorSmsHelper::XML_PATH_NEW_INVOICE_MESSAGE,
            VendorSmsHelper::XML_PATH_NEW_SHIPMENT_MESSAGE,
            VendorSmsHelper::XML_PATH_NEW_CREDITMEMO_MESSAGE,
            VendorSmsHelper::XML_PATH_ADMIN_PENDING_VENDOR_MESSAGE,
            self::XML_PATH_CUSTOMER_DELETE_MESSAGE,
            self::XML_PATH_CUSTOMER_EDIT_MESSAGE,
            self::XML_PATH_VENDOR_REGISTER_MESSAGE
        ];

        foreach($msgList as $msg) {
            if($this->template === $this->getConfigValue($msg, ScopeInterface::SCOPE_STORE)) {
                return $this->getConfigValue($msg."_id");
            }
        }

        $msgList = [
            SmsHelper::XML_PATH_CUSTOMER_NEW_ORDER_MESSAGE_BY_PAYMENT_METHOD,
            SmsHelper::XML_PATH_CUSTOMER_NEW_ORDER_MESSAGE_BY_SHIPPING_METHOD,
            SmsHelper::XML_PATH_CUSTOMER_NEW_INVOICE_MESSAGE_BY_PAYMENT_METHOD,
            SmsHelper::XML_PATH_CUSTOMER_NEW_INVOICE_MESSAGE_BY_SHIPPING_METHOD,
            SmsHelper::XML_PATH_CUSTOMER_NEW_SHIPMENT_MESSAGE_BY_SHIPPING_METHOD,
            SmsHelper::XML_PATH_CUSTOMER_NEW_SHIPMENT_MESSAGE_BY_PAYMENT_METHOD,
            SmsHelper::XML_PATH_CUSTOMER_NEW_CREDITMEMO_MESSAGE_BY_SHIPPING_METHOD,
            SmsHelper::XML_PATH_CUSTOMER_NEW_CREDITMEMO_MESSAGE_BY_PAYMENT_METHOD,
            SmsHelper::XML_PATH_CUSTOMER_ORDER_STATUS_CHANGED_MESSAGE
        ];

        foreach($msgList as $msg)
        {
            $jsonList = $this->getConfigValue($msg, ScopeInterface::SCOPE_STORE);
            if (empty($jsonList)) continue;
            $messageConvertedToArray = json_decode($jsonList, true);
            if (!is_array($messageConvertedToArray)) continue;

            foreach ($messageConvertedToArray as $key => $message)
            {
                if (
                    isset($message['message']) &&
                    isset($message['message_id']) &&
                    $this->template == $message['message']
                ) {
                    return $message['message_id'];
                }
            }
        }

        return "";
    }

    public function setTemplateName($template)
    {
        $this->template = $template;
        return $this;
    }

    protected function getConfigValue($path, $scope = ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        return $this->scopeConfig->getValue($path, $scope, $storeId);
    }
}
