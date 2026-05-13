<?php
namespace Vnecoms\SmsAlghaDdm\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_URL = 'vsms/settings/alghaddm_api_url';
    const XML_PATH_API     = 'vsms/settings/alghaddm_api';
    const XML_PATH_SENDER  = 'vsms/settings/alghaddm_sender';
    const XML_PATH_USERNAME  = 'vsms/settings/alghaddm_username';

    /**
     * Get Api Key
     *
     * @return string
     */
    public function getApiUrl(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL);
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
    public function getUserName(){
        return $this->scopeConfig->getValue(self::XML_PATH_USERNAME);
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
