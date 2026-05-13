<?php
namespace Vnecoms\SmsGateway\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/smsgateway_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/smsgateway_password';
    const XML_PATH_SENDER       = 'vsms/settings/smsgateway_sender';
    
    /**
     * Get user name
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
     * Get sender
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }
}