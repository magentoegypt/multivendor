<?php
namespace Vnecoms\VendorsPage\Block\Home;

class About extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $_pageHelper;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var string
     */
    protected $_configPath;
    
    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $_configHelper;
    
    /**
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\VendorsPage\Helper\Data $pageHelper
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDatabase
     * @param \Magento\Framework\Filesystem $filesystem
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper,
        array $data = []
    ) {
        $this->_pageHelper = $pageHelper;
        $this->_coreRegistry = $registry;
        $this->_configHelper = $configHelper;
        
        parent::__construct($context, $data);
    }
    
    /**
     * Get Vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }

    /**
     * Get vendor description
     *
     * @return string
     */
    public function getVendorDescription()
    {
        return $this->_pageHelper->getVendorDescription($this->getVendor()->getId());
    }
    
    public function _toHtml()
    {
        if (/* !$this->getVendorDescription() ||  */!$this->_pageHelper->canShowSellerDescription()) {
            return '';
        }
        return parent::_toHtml();
    }
}
