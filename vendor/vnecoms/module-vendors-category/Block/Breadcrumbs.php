<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

/**
 * Catalog breadcrumbs.
 */

namespace Vnecoms\VendorsCategory\Block;

use Vnecoms\VendorsCategory\Helper\Category;
use Magento\Framework\View\Element\Template\Context;

class Breadcrumbs extends \Magento\Framework\View\Element\Template
{
    /**
     * Catalog data.
     *
     * @var Category
     */
    protected $_catalogData = null;

    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $vendorPageHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $vendorConfig;

    /**
     * Breadcrumbs constructor.
     *
     * @param Context                            $context
     * @param Category                           $catalogData
     * @param \Vnecoms\VendorsPage\Helper\Data   $vendorPageHelper
     * @param \Magento\Framework\Registry        $registry
     * @param \Vnecoms\VendorsConfig\Helper\Data $vendorConfig
     * @param array                              $data
     */
    public function __construct(Context $context,
     Category $catalogData,
     \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper,
    \Magento\Framework\Registry $registry,
    \Vnecoms\VendorsConfig\Helper\Data $vendorConfig,
     array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->vendorPageHelper = $vendorPageHelper;
        $this->_catalogData = $catalogData;
        $this->vendorConfig = $vendorConfig;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve HTML title value separator (with space).
     *
     * @param null|string|bool|int|Store $store
     *
     * @return string
     */
    public function getTitleSeparator($store = null)
    {
        $separator = (string) $this->_scopeConfig->getValue('catalog/seo/title_separator', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);

        return ' '.$separator.' ';
    }

    /**
     * Preparing layout.
     *
     * @return \Magento\Catalog\Block\Breadcrumbs
     */
    protected function _prepareLayout()
    {
        if ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
            $label = $this->vendorConfig->getVendorConfig('general/store_information/name', $this->getVendor()->getId());
            if (!$label) {
                $label = 'Home';
            }
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                    'label' => __($label),
                    'title' => __('Go to Vendor Page'),
                    'link' => $this->getVendorPageUrl(),
                ]
            );

            $title = [];
            $path = $this->_catalogData->getBreadcrumbPath();

            foreach ($path as $name => $breadcrumb) {
                $breadcrumbsBlock->addCrumb($name, $breadcrumb);
                $title[] = $breadcrumb['label'];
            }

            $this->pageConfig->getTitle()->set(implode($this->getTitleSeparator(), array_reverse($title)));
        }

        return parent::_prepareLayout();
    }

    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }

    public function getVendorPageUrl()
    {
        return $this->vendorPageHelper->getUrl($this->getVendor());
    }
}
