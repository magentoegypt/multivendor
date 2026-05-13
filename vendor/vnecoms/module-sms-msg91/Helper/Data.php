<?php
namespace Vnecoms\SmsMsg91\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_URL = 'vsms/settings/msg91_api_url';
    const XML_PATH_API     = 'vsms/settings/msg91_api';
    const XML_PATH_SENDER  = 'vsms/settings/msg91_sender';
	const XML_PATH_UNICODE = 'vsms/settings/msg91_unicode';
    const XML_PATH_DLT = 'vsms/settings/dlt_template_id';

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
    public function getSender(){
        return $this->scopeConfig->getValue(self::XML_PATH_SENDER);
    }

    /**
     * Get Api Key
     *
     * @return string
     */
    public function getDltTemplateId(){
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL);
    }


    /**
     * Is Unicode
     *
     * @return bool
     */
    public function isUnicode(){
        return $this->scopeConfig->getValue(self::XML_PATH_UNICODE);
    }
}
