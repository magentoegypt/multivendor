<?php
namespace Vnecoms\SmsTermii\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API = 'vsms/settings/termii_api';
    const XML_PATH_SENDER  = 'vsms/settings/termii_sender_id';
	const XML_PATH_CHANNEL = 'vsms/settings/termii_channel';

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
	
	/**
     * Is Unicode
     *
     * @return bool
     */
    public function getChannel(){
        return $this->scopeConfig->getValue(self::XML_PATH_CHANNEL);
    }
}
