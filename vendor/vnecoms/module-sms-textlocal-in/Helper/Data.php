<?php
namespace Vnecoms\SmsTextLocalIn\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API     = 'vsms/settings/textlocalin_api';
    const XML_PATH_SENDER  = 'vsms/settings/textlocalin_sender';
    
    /**
     * Get Api Key
     * 
     * @return string
     */
    public function getApiKey(){
        return $this->scopeConfig->getValue(self::XML_PATH_API);
    }
    
    /**
     * Get Api Key
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }
}