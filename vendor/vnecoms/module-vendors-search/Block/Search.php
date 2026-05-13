<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsSearch\Block;

/**
 * Class Search.
 */
class Search extends \Magento\Framework\View\Element\Template
{
    /**
     * @var array
     */
    protected $jsLayout;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    protected $searchHelper;

    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $vendorConfig;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\VendorsSearch\Helper\Data $searchHelper,
        \Vnecoms\VendorsConfig\Helper\Data $vendorConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->searchHelper = $searchHelper;
        $this->vendorConfig = $vendorConfig;

        $this->jsLayout = isset($data['jsLayout']) ? $data['jsLayout'] : [];
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        return \Laminas\Json\Json::encode($this->jsLayout);
    }

    /**
     * Get Vendor.
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }

    public function getVendorName()
    {
        $label = $this->vendorConfig->getVendorConfig('general/store_information/name', $this->getVendor()->getId());
        if (!$label) {
            return $this->getVendor()->getVendorId();
        }
        return $label;
    }

    public function getSuggestUrl()
    {
        return $this->searchHelper->getSuggestUrl();
    }
}
