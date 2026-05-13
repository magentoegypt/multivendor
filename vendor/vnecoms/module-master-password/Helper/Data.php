<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\MasterPassword\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const XML_PATH_EXTENSION_ENABLED = 'master_password/general/enabled';
    const XML_PATH_MASTER_PASSWORD = 'master_password/general/password';

    /**
     * Get master password
     * 
     * @return int
     */
    public function getMasterPassword(){
        return $this->scopeConfig->getValue(self::XML_PATH_MASTER_PASSWORD);
    }
    
    /**
     * Is enabled extension
     * 
     * @return boolean
     */
    public function isEnabledExtension(){
        return $this->scopeConfig->getValue(self::XML_PATH_EXTENSION_ENABLED) && $this->getMasterPassword();
    }
}
