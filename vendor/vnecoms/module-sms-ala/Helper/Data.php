<?php
namespace Vnecoms\SmsAla\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_KEY  = 'vsms/settings/smsala_apikey';
    const XML_PATH_API_PASS = 'vsms/settings/smsala_apipassword';
    const XML_PATH_SENDER   = 'vsms/settings/smsala_sender';
    const XML_PATH_UNICODE  = 'vsms/settings/smsala_unicode';

    /**
     * @var \Magento\Framework\Encryption\Encryptor
     */
    protected $crypt;

    /**
     * Data constructor.
     * @param Context $context
     * @param \Magento\Framework\Encryption\Encryptor $crypt
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Encryption\Encryptor $crypt
    ) {
        $this->crypt = $crypt;
        parent::__construct($context);
    }


    /**
     * Get API key
     *
     * @return string
     */
    public function getApiKey(){
        return $this->crypt->decrypt($this->scopeConfig->getValue(self::XML_PATH_API_KEY));
    }

    /**
     * Get API Password
     *
     * @return string
     */
    public function getApiPassword(){
        return $this->crypt->decrypt($this->scopeConfig->getValue(self::XML_PATH_API_PASS));
    }

    /**
     * Get sender
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }

    /**
     * @return bool
     */
    public function isUnicode(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_UNICODE);
    }
}
