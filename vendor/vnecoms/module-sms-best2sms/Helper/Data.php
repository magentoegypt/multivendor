<?php
namespace Vnecoms\SmsBest2Sms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_SENDER       = 'vsms/settings/smsbest2sms_sender';
    const XML_PATH_USERNAME     = 'vsms/settings/smsbest2sms_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/smsbest2sms_password';
    const XML_PATH_UNICODE      = 'vsms/settings/smsbest2sms_unicode';
    
    /**
     * Get Sender
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }
    
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
     * Is Unicode
     *
     * @return boolean
     */
    public function isUnicode(){
        return $this->scopeConfig->getValue(self::XML_PATH_UNICODE);
    }
}
