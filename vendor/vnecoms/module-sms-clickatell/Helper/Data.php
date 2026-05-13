<?php
namespace Vnecoms\SmsClickatell\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_KEY   = 'vsms/settings/clickatell_apikey';
    
    /**
     * Get API Key
     * 
     * @return string
     */
    public function getApiKey(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_KEY);
    }
}