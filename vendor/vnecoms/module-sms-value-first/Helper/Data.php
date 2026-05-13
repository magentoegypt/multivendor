<?php
namespace Vnecoms\SmsValueFirst\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_URL          = 'vsms/settings/smsvaluefirst_url';
    const XML_PATH_USERNAME     = 'vsms/settings/smsvaluefirst_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/smsvaluefirst_password';
    const XML_PATH_SENDER       = 'vsms/settings/smsvaluefirst_sender';
    
    /**
     * Get Gateway Url
     * 
     * @return string
     */
    public function getGatewayUrl(){
        return $this->scopeConfig->getValue(self::XML_PATH_URL);
    }
    
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