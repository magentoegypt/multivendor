<?php
namespace Vnecoms\SmsOurSms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/oursms_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/oursms_password';
    const XML_PATH_SENDER       = 'vsms/settings/oursms_sender';
    const XML_PATH_IS_UNICODE	= 'vsms/settings/oursms_unicode';
    
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
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }
	
	/**
     * Is testing mode
     * 
     * @return boolean
     */
    public function isUnicode(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_IS_UNICODE);
    }
}
