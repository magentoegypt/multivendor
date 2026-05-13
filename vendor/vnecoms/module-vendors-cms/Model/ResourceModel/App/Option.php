<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\ResourceModel\App;

/**
 * Class Option.
 */
class Option extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table.
     */
    protected function _construct()
    {
        $this->_init('vnecoms_vendor_cms_app_option', 'option_id');
    }
}
