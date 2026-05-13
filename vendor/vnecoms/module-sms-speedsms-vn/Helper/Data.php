<?php
namespace Vnecoms\SmsSpeedSmsVn\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_TOKEN     = 'vsms/settings/speedsmsvn_token';
    const XML_PATH_TYPE      = 'vsms/settings/speedsmsvn_type';
    
    /**
     * Get token
     * 
     * @return string
     */
    public function getToken(){
        return $this->scopeConfig->getValue(self::XML_PATH_TOKEN);
    }
    
    /**
     * Get type
     *
     * @return string
     */
    public function getType(){
        return $this->scopeConfig->getValue(self::XML_PATH_TYPE);
    }
}