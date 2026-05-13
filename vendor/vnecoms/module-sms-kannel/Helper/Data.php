<?php
namespace Vnecoms\SmsKannel\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_URL          = 'vsms/settings/smskannel_url';
    const XML_PATH_USERNAME     = 'vsms/settings/smskannel_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/smskannel_password';
    
    /**
     * Get URL
     *
     * @return string
     */
    public function getUrl(){
        return $this->scopeConfig->getValue(self::XML_PATH_URL);
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
}
