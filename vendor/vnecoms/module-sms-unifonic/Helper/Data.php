<?php
namespace Vnecoms\SmsUnifonic\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_APP_ID     = 'vsms/settings/unifonic_app_id';
    const XML_PATH_SENDER_ID     = 'vsms/settings/unifonic_sender_id';
    
    /**
     * Get username
     * 
     * @return string
     */
    public function getAppId(){
        return $this->scopeConfig->getValue(self::XML_PATH_APP_ID);
    }
    
     /**
     * Get username
     * 
     * @return string
     */
    public function getSenderId(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER_ID);
    }
}