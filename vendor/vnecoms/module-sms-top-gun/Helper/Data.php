<?php
namespace Vnecoms\SmsTopGun\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_KEY               = 'vsms/settings/topgun_api';
    const XML_PATH_SENDER_NAME                = 'vsms/settings/topgun_sender_name';
    
    /**
     * @return string
     */
    public function getApiKey(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_KEY);
    }


    /**
     * @return string
     */
    public function getSenderName(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER_NAME);
    }

}

