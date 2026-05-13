<?php
namespace Vnecoms\SmsUigtc\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_KEY  = 'vsms/settings/uigtc_apikey';
    const XML_PATH_SENDER   = 'vsms/settings/uigtc_sender';

    /**
     * Get api key
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
}
