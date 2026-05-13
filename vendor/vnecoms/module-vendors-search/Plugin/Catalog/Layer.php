<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsSearch\Plugin\Catalog;

class Layer
{
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
    }
    /**
     * Get current vendor.
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }

    /**
     * @param \Magento\Catalog\Model\Layer $layer
     * @param $result
     * @return mixed
     */
    public function afterGetProductCollection(
        \Magento\Catalog\Model\Layer $layer,
        $result
    )
    {

        $isSearchResultPage = $this->_coreRegistry->registry('search_result_page');

        if (!$isSearchResultPage) {
            return $result;
        }

        if ($this->getVendor()) {
            $result->addAttributeToFilter('vendor_id', $this->getVendor()->getId());
        }

        
        return $result;
    }
}
