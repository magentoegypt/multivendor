<?php
namespace Vnecoms\SmsTaqnyat\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_TOKEN       = 'vsms/settings/taqnyat_token';
    const XML_PATH_SENDER     = 'vsms/settings/taqnyat_sender';

    /**
     * Get Token
     *
     * @return string
     */
    public function getToken(){
        return $this->scopeConfig->getValue(self::XML_PATH_TOKEN);
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
