<?php
namespace Vnecoms\SmsIletiMerkezi\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_USERNAME     = 'vsms/settings/ileti_merkezi_username';
    const XML_PATH_PASSWORD     = 'vsms/settings/ileti_merkezi_password';
    const XML_PATH_SENDER_ID    = 'vsms/settings/ileti_merkezi_sender';
    
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
     * Get Sender
     *
     * @return string
     */
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER_ID);
    }
}
