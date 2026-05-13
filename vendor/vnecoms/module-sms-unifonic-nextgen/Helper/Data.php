<?php
namespace Vnecoms\SmsUnifonicNextgen\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USER       = 'vsms/settings/unifonic_nextgen_username';
    const XML_PATH_PASS       = 'vsms/settings/unifonic_nextgen_password';
    const XML_PATH_APP_ID     = 'vsms/settings/unifonic_nextgen_app_id';
    const XML_PATH_SENDER     = 'vsms/settings/unifonic_nextgen_sender';

    /**
     * Get User Name
     *
     * @return string
     */
    public function getUsername(){
        return $this->scopeConfig->getValue(self::XML_PATH_USER);
    }

    /**
     * Get Password
     *
     * @return string
     */
    public function getPassword(){
        return $this->scopeConfig->getValue(self::XML_PATH_PASS);
    }


    /**
     * Get APP ID
     *
     * @return string
     */
    public function getAppId(){
        return $this->scopeConfig->getValue(self::XML_PATH_APP_ID);
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
