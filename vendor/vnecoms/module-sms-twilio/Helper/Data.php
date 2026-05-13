<?php
namespace Vnecoms\SmsTwilio\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ACCOUNT_SID  = 'vsms/settings/twilio_account_sid';
    const XML_PATH_AUTH_TOKEN   = 'vsms/settings/twilio_auth_token';
    const XML_PATH_PHONE_NUM    = 'vsms/settings/twilio_phone_num';
    
    /**
     * Get twilio account sid
     * 
     * @return string
     */
    public function getAccountSid(){
        return $this->scopeConfig->getValue(self::XML_PATH_ACCOUNT_SID);
    }
    
    /**
     * Get twilio auth token
     *
     * @return string
     */
    public function getAuthToken(){
        return $this->scopeConfig->getValue(self::XML_PATH_AUTH_TOKEN);
    }
    
    /**
     * Get twilio phone number
     *
     * @return string
     */
    public function getPhoneNumber(){
        return $this->scopeConfig->getValue(self::XML_PATH_PHONE_NUM);
    }
}