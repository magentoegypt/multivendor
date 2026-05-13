<?php
namespace Vnecoms\SmsWeb2sms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ACCOUNT_ID     = 'vsms/settings/web2sms_accid';
    const XML_PATH_PASSWORD     = 'vsms/settings/web2sms_password';
    const XML_PATH_SENDER       = 'vsms/settings/web2sms_sender';
    const XML_PATH_SECRET_KEY      = 'vsms/settings/web2sms_secret_key';

    /**
     * Get Account Id
     *
     * @return string
     */
    public function getAccountId(){
        return $this->scopeConfig->getValue(self::XML_PATH_ACCOUNT_ID);
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
     * Get secret key
     *
     * @return string
     */
    public function getSecretKey(){
        return $this->scopeConfig->getValue(self::XML_PATH_SECRET_KEY);
    }
}
