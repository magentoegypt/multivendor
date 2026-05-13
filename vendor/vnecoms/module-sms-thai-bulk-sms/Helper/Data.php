<?php
namespace Vnecoms\SmsThaiBulkSms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/thaibulksms_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/thaibulksms_password';
    const XML_PATH_SENDER       = 'vsms/settings/thaibulksms_sender';
    const XML_PATH_MESSAGE_TYPE = 'vsms/settings/thaibulksms_message_type';
    const XML_PATH_IS_TESTING   = 'vsms/settings/thaibulksms_is_test';
    
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
     * Get Sender
     *
     * @return string
     */
    public function getMessageType(){
        return $this->scopeConfig->getValue(self::XML_PATH_MESSAGE_TYPE);
    }
    
    /**
     * Is testing mode
     * 
     * @return boolean
     */
    public function isTestingMode(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_IS_TESTING);
    }    
    
}
