<?php
namespace Vnecoms\SmsBulkSms\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/bulksms_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/bulksms_password';
    const XML_PATH_IS_UNICODE   = 'vsms/settings/bulksms_is_unicode';
    
    /**
     * Get username
     * 
     * @return string
     */
    public function getUsername(){
        return $this->scopeConfig->getValue(self::XML_PATH_USERNAME);
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
     * Get encoding
     * 
     * @return string
     */
    public function getEncoding(){
        return $this->scopeConfig->getValue(self::XML_PATH_IS_UNICODE)?'UNICODE':'TEXT';
    }
}
