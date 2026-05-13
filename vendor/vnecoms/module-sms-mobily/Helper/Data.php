<?php
namespace Vnecoms\SmsMobily\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/mobily_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/mobily_password';
    const XML_PATH_SENDER_NAME  = 'vsms/settings/mobily_sender';
    const XML_PATH_APP_TYPE     = 'vsms/settings/mobily_app_type';
    
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
    
    /**
     * Get App Type
     * 
     * @return mixed
     */
    public function getAppType(){
        return $this->scopeConfig->getValue(self::XML_PATH_APP_TYPE);
    }
}