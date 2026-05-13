<?php
namespace Vnecoms\SmsSemysms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_TOKEN     = 'vsms/settings/semysms_token';
    const XML_PATH_DEVICE    = 'vsms/settings/semysms_device';
    
    /**
     * Get token
     * 
     * @return string
     */
    public function getToken(){
        return $this->scopeConfig->getValue(self::XML_PATH_TOKEN);
    }
	
	/**
     * Get device
     * 
     * @return string
     */
    public function getDevice(){
        return $this->scopeConfig->getValue(self::XML_PATH_DEVICE);
    }
}
