<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Block\Category;

/**
 * Class View.
 */
class View extends \Magento\Framework\View\Element\Template implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Vnecoms\VendorsCategory\Helper\Category
     */
    protected $_categoryHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry                      $registry
     * @param \Vnecoms\VendorsCategory\Helper\Category         $categoryHelper
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsCategory\Helper\Category $categoryHelper,
        array $data = []
    ) {
        $this->_categoryHelper = $categoryHelper;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->getLayout()->createBlock('Vnecoms\VendorsCategory\Block\Breadcrumbs');

        $category = $this->getCurrentCategory();
        if ($category) {
            $title = $category->getMetaTitle();
            if ($title) {
                $this->pageConfig->getTitle()->set($title);
            }
            $description = $category->getMetaDescription();
            if ($description) {
                $this->pageConfig->setDescription($description);
            }
            $keywords = $category->getMetaKeywords();
            if ($keywords) {
                $this->pageConfig->setKeywords($keywords);
            }
            if ($this->_categoryHelper->canUseCanonicalTag()) {
                $this->pageConfig->addRemotePageAsset(
                    $category->getUrl(),
                    'canonical',
                    ['attributes' => ['rel' => 'canonical']]
                );
            }

            $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
            if ($pageMainTitle) {
                $pageMainTitle->setPageTitle($this->getCurrentCategory()->getName());
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getProductListHtml()
    {
        return $this->getChildHtml('product_list');
    }

    /**
     * Retrieve current category model object.
     *
     * @return \Vnecoms\VendorsCategory\Model\Category
     */
    public function getCurrentCategory()
    {
        if (!$this->hasData('current_vendor_category')) {
            $this->setData('current_vendor_category', $this->_coreRegistry->registry('current_vendor_category'));
        }

        return $this->getData('current_vendor_category');
    }

    /**
     * Return identifiers for produced content.
     *
     * @return array
     */
    public function getIdentities()
    {
        return $this->getCurrentCategory()->getIdentities();
    }

    /**
     * Get vendor object.
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        $vendor = $this->_coreRegistry->registry('vendor');

        return $vendor;
    }

    /**
     * Get is hide product.
     *
     * @return int|bool
     */
    public function isHideProduct()
    {
        return $this->getCurrentCategory()->getIsHideProduct();
    }
}
