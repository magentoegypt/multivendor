<?php
namespace Vnecoms\SmsMsegat\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/msegat_username';
    const XML_PATH_API_KEY      = 'vsms/settings/msegat_api_key';
    const XML_PATH_SENDER       = 'vsms/settings/msegat_sender';
    const XML_PATH_IS_UNICODE	= 'vsms/settings/msegat_unicode';
    
    /**
     * Get username
     * 
     * @return string
     */
    public function getUsername(){
        return $this->scopeConfig->getValue(self::XML_PATH_USERNAME);
    }
    
    /**
     * Get password
     *
     * @return string
     */
    public function getApiKey(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_KEY);
    }
    
    /**
     * Get Sender
     * 
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }
	
	/**
     * Is testing mode
     * 
     * @return boolean
     */
    public function isUnicode(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_IS_UNICODE);
    }
}
