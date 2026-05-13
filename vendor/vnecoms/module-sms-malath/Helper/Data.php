<?php
namespace Vnecoms\SmsMalath\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/malath_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/malath_password';
    const XML_PATH_SENDER_NAME  = 'vsms/settings/malath_sender';
    
    /**
     * Get user name
     * 
     * @return string
     */
    public function getUserName(){
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
     * Get Sender Name
     * 
     * @return string
     */
    public function getSenderName(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER_NAME);
    }
}