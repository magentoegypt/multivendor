<?php
namespace Vnecoms\SmsFastMobileSms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USER_NAME    = 'vsms/settings/fastmobilesms_user_name';
    const XML_PATH_PASSWORD     = 'vsms/settings/fastmobilesms_password';
    const XML_PATH_SENDER       = 'vsms/settings/fastmobilesms_sender';
    
    /**
     * Get username
     * 
     * @return string
     */
    public function getUserName(){
        return $this->scopeConfig->getValue(self::XML_PATH_USER_NAME);
    }
    
    /**
     * Get password
     *
     * @return string
     */
    public function getPassword(){
        return $this->scopeConfig->getValue(self::XML_PATH_PASSWORD);
    }
    
    /**
     * Get Sender
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }
}
