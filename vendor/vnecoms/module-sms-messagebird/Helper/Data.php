<?php
namespace Vnecoms\SmsMessagebird\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ACCESS_KEY   = 'vsms/settings/messagebird_access_key';
    const XML_PATH_ORIGINATOR    = 'vsms/settings/messagebird_originator';
    
    /**
     * Get messagebird access key
     * 
     * @return string
     */
    public function getAccessKey(){
        return $this->scopeConfig->getValue(self::XML_PATH_ACCESS_KEY);
    }
    
    /**
     * Get twilio phone number
     *
     * @return string
     */
    public function getOriginator(){
        return $this->scopeConfig->getValue(self::XML_PATH_ORIGINATOR);
    }
}