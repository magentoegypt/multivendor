<?php
namespace Vnecoms\SmsWavecell\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API                  = 'vsms/settings/wavecell_api';
    const XML_PATH_SUBACCOUNT_ID        = 'vsms/settings/wavecell_subaccount_id';
    const XML_PATH_SENDER               = 'vsms/settings/wavecell_sender';
    
    /**
     * Get api
     * 
     * @return string
     */
    public function getApi(){
        return $this->scopeConfig->getValue(self::XML_PATH_API);
    }
    
    /**
     * Get type
     *
     * @return string
     */
    public function getSubAccountId(){
        return $this->scopeConfig->getValue(self::XML_PATH_SUBACCOUNT_ID);
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