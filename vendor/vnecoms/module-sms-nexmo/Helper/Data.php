<?php
namespace Vnecoms\SmsNexmo\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_KEY      = 'vsms/settings/nexmo_api_key';
    const XML_PATH_API_SECRET   = 'vsms/settings/nexmo_api_secret';
    const XML_PATH_SENDER       = 'vsms/settings/nexmo_sender';
    const XML_PATH_UNICODE      = 'vsms/settings/nexmo_unicode';
    
    /**
     * Get api key
     * 
     * @return string
     */
    public function getApiKey(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_KEY);
    }
    
    /**
     * Get api secret
     *
     * @return string
     */
    public function getApiSecret(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_SECRET);
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
     * Is unicode message
     * 
     * @return boolean
     */
    public function isUnicode(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_UNICODE);
    }
}