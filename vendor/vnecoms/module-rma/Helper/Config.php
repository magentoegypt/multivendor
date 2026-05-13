<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/28/2016
 * Time: 10:36 AM
 */

namespace Vnecoms\RMA\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_ORDER_EXPIRY_DAY = 'rma/general/order_expiry_day';
    const XML_PATH_ALLOW_GUESTS_REQUEST = 'rma/general/allow_guests_request';
    const XML_PATH_ALLOW_PRINT = 'rma/general/allow_print';
    const XML_PATH_ENABLE_REASONS = 'rma/general/enable_reasons';
    const XML_PATH_ALLOW_OTHER_REASONS = 'rma/general/allow_other_reasons';
    const XML_PATH_ALLOW_PER_ORDER = 'rma/general/allow_per_order';
    const XML_PATH_ALLOW_FILE_EXTENSION = 'rma/general/allow_file_extension';
    const XML_PATH_RESOLVED_EXPIRY_DAY = 'rma/general/max_resolve_time';

    const XML_PATH_INCREMENT_PREFIX = 'rma/increment/prefix';
    const XML_PATH_INCREMENT_SUFFIX = 'rma/increment/suffix';
    const XML_PATH_INCREMENT_START= 'rma/increment/start_value';
    const XML_PATH_INCREMENT_STEP = 'rma/increment/step';
    const XML_PATH_INCREMENT_NUMBER = 'rma/increment/number_length';


    const XML_PATH_ENABLE_POLICY = 'rma/policy/enable_policy';
    const XML_PATH_POLICY_BLOCK = 'rma/policy/policy_block';
    const XML_PATH_ENABLE_GUIDE = 'rma/policy/enable_guide';
    const XML_PATH_POLICY_BLOCK_GUIDE = 'rma/policy/policy_block_guide';

    const XML_PATH_ENABLE_CONTACTS = 'rma/contacts/enable_contacts';
    const XML_PATH_CONTACTS_MAME = 'rma/contacts/contacts_name';
    const XML_PATH_CONTACTS_EMAIL = 'rma/contacts/contacts_email';
    const XML_PATH_CONTACTS_ADDRESS = 'rma/contacts/contacts_address';
    const XML_PATH_CONTACTS_TEMPLATE_CUSTOMER  = 'rma/contacts/message_template_customer';
    const XML_PATH_CONTACTS_TEMPLATE_ADMIN = 'rma/contacts/message_template_admin';
    const XML_PATH_EMAIL_IDENTIFY = 'rma/contacts/email_identity';


    const XML_PATH_ENABLE_RECAPTCHA = 'rma/recaptcha/enabled_recaptcha';
    const XML_PATH_SITE_KEY = 'rma/recaptcha/site_key';
    const XML_PATH_SECRET_KEY = 'rma/recaptcha/secret_key';
    const XML_PATH_LANGUAGE_RECAPTCHA = 'rma/recaptcha/language_recaptcha';

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory
     */
    protected $shipmentItemCollection;

    /**
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $shipmentFactory;

    /**
     * Config constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory $collectionFactory
     * @param \Magento\Sales\Model\Order\Shipment $shipmentFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory $collectionFactory,
        \Magento\Sales\Model\Order\Shipment $shipmentFactory
    ) {

        $this->shipmentItemCollection = $collectionFactory;
        $this->shipmentFactory = $shipmentFactory;
        parent::__construct($context);
    }


    /**
     * get config order_expiry_day
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function oderExpiryDay()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_ORDER_EXPIRY_DAY, $storeScope);
    }
    /**
     * get config allow_guests_request
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function allowGuestsRequest()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_ALLOW_GUESTS_REQUEST, $storeScope);
    }
    /**
     * get config allow_print
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function allowPrint()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_ALLOW_PRINT, $storeScope);
    }
    /**
     * get config enable_reasons
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function enableReasons()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_ENABLE_REASONS, $storeScope);
    }


    /**
     * get config allow_other_reasons
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function allowOtherReasons()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_ALLOW_OTHER_REASONS, $storeScope);
    }
    /**
     * get config allow_per_order
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function allowPerOrder()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_ALLOW_PER_ORDER, $storeScope);
    }
    /**
     * get config allow_file_extension
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function allowFileExtension()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_ALLOW_FILE_EXTENSION, $storeScope);
    }

    /**
     * get config prefix
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function getIncrementPrefix()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
        return $this->scopeConfig->getValue(self::XML_PATH_INCREMENT_PREFIX, $storeScope);
    }

    /**
     * get config max_resolve_time
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function getMaximumTimeResolvedRequest()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_RESOLVED_EXPIRY_DAY, $storeScope);
    }


    /**
     * get config prefix
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function getIncrementSuffix()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
        return $this->scopeConfig->getValue(self::XML_PATH_INCREMENT_SUFFIX, $storeScope);
    }

    /**
     * get config prefix
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function getIncrementStartNumber()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
        return $this->scopeConfig->getValue(self::XML_PATH_INCREMENT_START, $storeScope);
    }


    /**
     * get config prefix
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function getIncrementStep()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
        return $this->scopeConfig->getValue(self::XML_PATH_INCREMENT_STEP, $storeScope);
    }


    /**
     * get config prefix
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function getIncrementNumber()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
        return $this->scopeConfig->getValue(self::XML_PATH_INCREMENT_NUMBER, $storeScope);
    }


    /**
     * get config enable_sendmail_before_close
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function enablePolicy()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_ENABLE_POLICY, $storeScope);
    }

    /**
     * get config enable_policy
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function policyBlock()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_POLICY_BLOCK, $storeScope);
    }
    /**
     * get config enable_guide
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function enableGuide()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_ENABLE_GUIDE, $storeScope);
    }
    /**
     * get config policy_block_guide
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function policyBlockGuide()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_POLICY_BLOCK_GUIDE, $storeScope);
    }



    /**
     * get config enable_contacts
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function enableContacts()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_ENABLE_CONTACTS, $storeScope);
    }

    /**
     * get config contacts_email
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function emailIdentity()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_EMAIL_IDENTIFY, $storeScope);
    }


    /**
     * get config contacts_name
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function contactsName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_CONTACTS_MAME, $storeScope);
    }
    /**
     * get config contacts_email
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function contactsEmail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_CONTACTS_EMAIL, $storeScope);
    }

    /**
     * get config contacts_address
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function contactsAddress()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_CONTACTS_ADDRESS, $storeScope);
    }

    /**
     * get config contacts_address
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function emailTemplateMessageToCustomer()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_CONTACTS_TEMPLATE_CUSTOMER, $storeScope);
    }

    /**
     * get config contacts_address
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function emailTemplateMessageToAdmin()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_CONTACTS_TEMPLATE_ADMIN, $storeScope);
    }

    /**
     * get config enable_recaptcha
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function enableRecaptcha()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_ENABLE_RECAPTCHA, $storeScope);
    }
    /**
     * get config site_key
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function siteKey()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_SITE_KEY, $storeScope);
    }
    /**
     * get config secret_key
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function secretKey()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_SECRET_KEY, $storeScope);
    }
    /**
     * get config language_recaptcha
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function languageRecaptcha()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_LANGUAGE_RECAPTCHA, $storeScope);
    }


    /**
     * Geo Ip from client
     * @return string
     */
    public function getClientIP()
    {

        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                return $_SERVER["HTTP_X_FORWARDED_FOR"];
            }

            if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                return $_SERVER["HTTP_CLIENT_IP"];
            }

            return $_SERVER["REMOTE_ADDR"];
        }

        if (getenv('HTTP_X_FORWARDED_FOR')) {
            return getenv('HTTP_X_FORWARDED_FOR');
        }

        if (getenv('HTTP_CLIENT_IP')) {
            return getenv('HTTP_CLIENT_IP');
        }

        return getenv('REMOTE_ADDR');
    }

    /**
     * function convertext
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function converText($text)
    {
        $text=str_replace('<p>&nbsp;</p>', '', $text);
        trim($text);
        return $text;
    }


    /**
     * get class for file
     * @param $file
     * @return string
     */
    public function getClassIcon($file)
    {
        $class= "";
        $path_attachment = pathinfo($file);
        if(!isset($path_attachment['extension'])) return $class;
        switch (strtolower($path_attachment['extension'])) {
            case 'jpg':
                $class = "icon-jpg";
                break;
            case 'jpeg':
                $class = "icon-jpeg";
                break;
            case 'png':
                $class = "icon-png";
                break;
            case 'gif':
                $class = "icon-gif";
                break;
            case 'pdf':
                $class = "fa-file-pdf-o";
                break;
            case 'zip':
                $class = "fa-file-archive-o";
                break;
            case 'rar':
                $class = "fa-file-archive-o";
                break;
            case 'txt':
                $class = "fa-file-text-o";
                break;
            case 'csv':
                $class = "fa-file-excel-o";
                break;
            case 'xlsx':
                $class = "fa-file-excel-o";
                break;
            case 'doc':
                $class = "fa-file-word-o";
                break;
            case 'docx':
                $class = "fa-file-word-o";
                break;
            default:
                $class = "fa-file-o";
                break;
        }
        return $class;
    }

    /**
     * @param $item
     * @return |null
     */
    public function getTrackingNumberByOrderItem($item) {
        $trackNumber = null;
        $shipmenItems = $this->shipmentItemCollection->create()->addFieldToFilter("order_item_id", $item->getId());
        if (!$shipmenItems->count()) return $trackNumber;

        foreach ($shipmenItems as $shipmenItem) {
            $shipment = $this->shipmentFactory->load($shipmenItem->getParentId());
            if (!$shipment->getId()) continue;
            $tracks = $shipment->getAllTracks();
            if (!count($tracks)) continue;
            $trackNumber = $tracks[0]->getTrackNumber();
            break;
        }

        return $trackNumber;
    }
}
