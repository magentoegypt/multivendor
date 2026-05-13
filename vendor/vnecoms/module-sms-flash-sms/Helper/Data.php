<?php
namespace Vnecoms\SmsFlashSms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USER     = 'vsms/settings/flashsms_user';
    const XML_PATH_PASS     = 'vsms/settings/flashsms_pass';
    const XML_PATH_SENDER	= 'vsms/settings/flashsms_sender';
    
    /**
     * Get User
     * 
     * @return string
     */
    public function getUser(){
        return $this->scopeConfig->getValue(self::XML_PATH_USER);
    }
	
	/**
     * Get User
     * 
     * @return string
     */
    public function getPassword(){
        return $this->scopeConfig->getValue(self::XML_PATH_PASS);
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