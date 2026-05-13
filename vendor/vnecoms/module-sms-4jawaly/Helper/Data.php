<?php
namespace Vnecoms\Sms4jawaly\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/sms4jawaly_user';
    const XML_PATH_PASSWORD     = 'vsms/settings/sms4jawaly_password';
    const XML_PATH_SENDER       = 'vsms/settings/sms4jawaly_sender';
    const XML_PATH_UNICODE      = 'vsms/settings/sms4jawaly_unicode';

    /**
     * Get User Name
     *
     * @return string
     */
    public function getUser(){
        return $this->scopeConfig->getValue(self::XML_PATH_USERNAME);
    }

    /**
     * Get Password
     *
     * @return string
     */
    public function getPassword(){
        return $this->scopeConfig->getValue(self::XML_PATH_PASSWORD);
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
