<?php
namespace Vnecoms\SmsCountry\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/smscountry_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/smscountry_password';
    const XML_PATH_SENDER_ID    = 'vsms/settings/smscountry_sender';
    const XML_PATH_IS_UNICODE   = 'vsms/settings/smscountry_is_unicode';
    
    /**
     * Get username
     * 
     * @return string
     */
    public function getUsername(){
        return $this->scopeConfig->getValue(self::XML_PATH_USERNAME);
    }
    
    /**
     * Get password
     *
     * @return string
     */
    public function getPassword(){
        return $this->scopeConfig->getValue(self::XML_PATH_PASSWORD);
    }
    
    /**
     * Get Sender
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER_ID);
    }
    
    /**
     * Get encoding
     * 
     * @return string
     */
    public function isUnicode(){
        return $this->scopeConfig->getValue(self::XML_PATH_IS_UNICODE);
    }
}
