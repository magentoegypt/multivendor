<?php
namespace Vnecoms\VendorsPage\Block\Profile;

class Link extends \Magento\Framework\View\Element\Template
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
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_pageHelper = $pageHelper;
        
        parent::__construct($context, $data);
    }

    /**
     * Get vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->getParentBlock()->getVendor();
    }
    
    /**
     * Can show link
     *
     * @return boolean
     */
    public function canShowLink()
    {
        return !$this->_coreRegistry->registry('is_home_page');
    }
    
    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->canShowLink()) {
            $profileBlock = $this->getParentBlock();
            $profileBlock->setVendorUrl($this->_pageHelper->getUrl($this->getVendor()));
        }
        return $this;
    }
}
