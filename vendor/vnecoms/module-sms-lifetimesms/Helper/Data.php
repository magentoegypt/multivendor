<?php
namespace Vnecoms\SmsLifetimesms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_TOKEN     = 'vsms/settings/lifetimesms_token';
    const XML_PATH_SECRET     = 'vsms/settings/lifetimesms_secret';
    const XML_PATH_SENDER   = 'vsms/settings/lifetimesms_sender';

    /**
     * Get token
     *
     * @return string
     */
    public function getToken(){
        return $this->scopeConfig->getValue(self::XML_PATH_TOKEN);
    }

	/**
     * Get secret
     *
     * @return string
     */
    public function getSecret(){
        return $this->scopeConfig->getValue(self::XML_PATH_SECRET);
    }

    /**
     * Get sender
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }
}
