<?php
namespace Vnecoms\SmsKapsystem\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/kapsystem_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/kapsystem_password';
    const XML_PATH_SOURCE       = 'vsms/settings/kapsystem_source';
    const XML_PATH_API_URL      = 'vsms/settings/kapsystem_api_url';
    
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
        return $this->scopeConfig->getValue(self::XML_PATH_SOURCE);
    }
    
    /**
     * Get API URL
     *
     * @return string
     */
    public function getApiUrl(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL);
    }
}