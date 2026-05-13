<?php
namespace Vnecoms\SmsKaleyra\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_URL          = 'vsms/settings/smskaleyra_url';
    const XML_PATH_API_KEY      = 'vsms/settings/smskaleyra_api_key';
    const XML_PATH_SENDER       = 'vsms/settings/smskaleyra_sender';
    
    /**
     * Get Gateway Url
     * 
     * @return string
     */
    public function getGatewayUrl(){
        return $this->scopeConfig->getValue(self::XML_PATH_URL);
    }
    
    /**
     * Get user name
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