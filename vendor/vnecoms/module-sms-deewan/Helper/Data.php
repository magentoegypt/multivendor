<?php
namespace Vnecoms\SmsDeewan\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API = 'vsms/settings/deewan_api';
    const XML_PATH_SENDER  = 'vsms/settings/deewan_sender';
    const XML_PATH_HOST = 'vsms/settings/deewan_api_url';

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
    public function getHost(){
        return $this->scopeConfig->getValue(self::XML_PATH_HOST);
    }
}
