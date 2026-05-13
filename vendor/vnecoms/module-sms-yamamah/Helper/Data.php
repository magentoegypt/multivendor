<?php
namespace Vnecoms\SmsYamamah\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USER                 = 'vsms/settings/yamamah_user';
    const XML_PATH_PASS                 = 'vsms/settings/yamamah_password';
    const XML_PATH_SENDER               = 'vsms/settings/yamamah_sender';
    
    /**
     * @return string
     */
    public function getUser(){
        return $this->scopeConfig->getValue(self::XML_PATH_USER);
    }
    
    /**
     * @return string
     */
    public function getPassword(){
        return $this->scopeConfig->getValue(self::XML_PATH_PASS);
    }

    /**
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }
}
