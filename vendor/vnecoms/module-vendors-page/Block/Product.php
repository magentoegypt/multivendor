<?php
namespace Vnecoms\VendorsPage\Block;

/**
 * Class View
 * @package Magento\Catalog\Block\Category
 */
class Product extends \Magento\Framework\View\Element\Template implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $_pageHelper;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $vendorHelper;

    /**
     * Product constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\VendorsPage\Helper\Data $pageHelper
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_pageHelper = $pageHelper;
        $this->vendorHelper = $vendorHelper;

        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $vendor = $this->_coreRegistry->registry('vendor');

        if ($this->_coreRegistry->registry('is_home_page')) {
            $title = $this->_pageHelper->getMetaTitle($vendor->getId());
            $storeNameVendor = $this->vendorHelper->getVendorStoreName($vendor->getId());
            if ($title) {
                $title = ucfirst($title);
            } else if (!$title && $storeNameVendor)  {
                $title = __("%1's home page", ucfirst($storeNameVendor));
            } else if (!$title && !$storeNameVendor) {
                $title = __("%1's home page", ucfirst($vendor->getVendorId()));
            } else {
                $title = __("Vendor's home page");
            }

            $this->pageConfig->getTitle()->set($title);

            $description = $this->_pageHelper->getMetaDescription($vendor->getId());
            $this->pageConfig->setDescription($description);

            $keywords = $this->_pageHelper->getMetaKeywords($vendor->getId());
            if ($keywords) {
                $this->pageConfig->setKeywords($keywords);
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
     * Check if category display mode is "Products Only"
     * @return bool
     */
    public function isProductMode()
    {
        return false;
    }

    /**
     * Check if category display mode is "Static Block and Products"
     * @return bool
     */
    public function isMixedMode()
    {
        return true;
    }

    /**
     * Check if category display mode is "Static Block Only"
     * For anchor category with applied filter Static Block Only mode not allowed
     *
     * @return bool
     */
    public function isContentMode()
    {
        return false;
    }

    public function getIdentities()
    {
        return ['vnecoms_credit_product_list'];
    }
}
