<?php
namespace Vnecoms\SmsGlobal\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_KEY      = 'vsms/settings/smsglobal_api_key';
    const XML_PATH_API_SECRET   = 'vsms/settings/smsglobal_api_secret';
    const XML_PATH_API_SENDER   = 'vsms/settings/smsglobal_api_sender';

    /**
     * Get api key
     * 
     * @return string
     */
    public function getApiKey(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_KEY);
    }
    
    /**
     * Get api secret
     *
     * @return string
     */
    public function getApiSecret(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_SECRET);
    }

    /**
     * Get Sender
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_SENDER);
    }
}
