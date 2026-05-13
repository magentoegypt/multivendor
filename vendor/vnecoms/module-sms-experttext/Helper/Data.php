<?php
namespace Vnecoms\SmsExpertText\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API = 'vsms/settings/experttext_api';
    const XML_PATH_API_SECRET     = 'vsms/settings/experttext_api_secret';
    const XML_PATH_SENDER  = 'vsms/settings/experttext_sender';
	const XML_PATH_UNICODE = 'vsms/settings/experttext_unicode';
	
    /**
     * Get Api Key
     *
     * @return string
     */
    public function getApiSecret(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_SECRET);
    }

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
    public function isUnicode(){
        return $this->scopeConfig->getValue(self::XML_PATH_UNICODE);
    }
}
