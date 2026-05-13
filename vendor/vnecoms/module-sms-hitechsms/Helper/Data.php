<?php
namespace Vnecoms\SmsHitechSms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_KEY  = 'vsms/settings/hitechsms_apikey';
    const XML_PATH_SENDER   = 'vsms/settings/hitechsms_sender';
    const XML_PATH_UNICODE  = 'vsms/settings/hitechsms_unicode';

    /**
     * Get API key
     *
     * @return string
     */
    public function getApiKey(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_KEY);
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
