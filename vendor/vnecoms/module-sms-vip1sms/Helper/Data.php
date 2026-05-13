<?php
namespace Vnecoms\SmsVip1sms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USER     = 'vsms/settings/vip1sms_username';
    const XML_PATH_PASS     = 'vsms/settings/vip1sms_password';
    const XML_PATH_SENDER   = 'vsms/settings/vip1sms_sender';
    const XML_PATH_UNICODE  = 'vsms/settings/vip1sms_unicode';

    /**
     * Get user name
     *
     * @return string
     */
    public function getUser(){
        return $this->scopeConfig->getValue(self::XML_PATH_USER);
    }

	/**
     * Get password
     *
     * @return string
     */
    public function getPass(){
        return $this->scopeConfig->getValue(self::XML_PATH_PASS);
    }

    /**
     * Get sender
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }

    /**
     * Is unicode message
     *
     * @return string
     */
    public function isUnicode(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_UNICODE);
    }
}
