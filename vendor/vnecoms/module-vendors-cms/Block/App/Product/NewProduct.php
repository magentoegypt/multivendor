<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Block\App\Product;

/**
 * New products app.
 */
class NewProduct extends \Magento\Catalog\Block\Product\Widget\NewWidget implements \Vnecoms\VendorsCms\Block\App\AppInterface
{
    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $vendor;

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    protected function getVendor()
    {
        if (!$this->vendor) {
            $this->vendor = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\Vendors\Model\Session')->getVendor();
        }

        return $this->vendor;
    }

    protected function _getProductCollection()
    {
    }
}
