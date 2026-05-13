<?php
namespace Vnecoms\SmsInfobip\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_USERNAME     = 'vsms/settings/infobip_username';
    const XML_PASSWORD     = 'vsms/settings/infobip_password';
    const XML_PATH_SENDER  = 'vsms/settings/infobip_sender';
    
    /**
     * Get username
     * 
     * @return string
     */
    public function getUserName(){
        return $this->scopeConfig->getValue(self::XML_USERNAME);
    }
    
    /**
     * Get password
     *
     * @return string
     */
    public function getPassword(){
        return $this->scopeConfig->getValue(self::XML_PASSWORD);
    }
    
    /**
     * Get Api Key
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }
}
