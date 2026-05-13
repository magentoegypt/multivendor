<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 23/12/2016
 * Time: 15:31.
 */

namespace Vnecoms\VendorsCms\Block\Html\Header;

use Magento\Framework\View\Element\Template;

class Logo extends \Magento\Theme\Block\Html\Header\Logo
{
    /**
     * Current template name.
     *
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsCms::header/logo.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /** @var \Vnecoms\VendorsPage\Helper\Data  */
    protected $_vendorPageHelper;

    /** @var \Vnecoms\VendorsConfig\Helper\Data  */
    protected $_configHelper;

    /** @var \Vnecoms\Vendors\Helper\Image  */
    protected $_imageHelper;

    /** @var \Vnecoms\Vendors\Block\Profile  */
    protected $_vendorProfile;


    /**
     * Logo constructor.
     * @param Template\Context $context
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper
     * @param \Vnecoms\Vendors\Helper\Image $imageHelper
     * @param \Vnecoms\VendorsConfig\Helper\Data $configHelper
     * @param \Vnecoms\Vendors\Block\Profile $vendorProfile
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper,
        \Vnecoms\Vendors\Helper\Image $imageHelper,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper,
        \Vnecoms\Vendors\Block\Profile $vendorProfile,
        array $data = []
    ) {
        parent::__construct($context, $fileStorageHelper, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_vendorHelper = $vendorHelper;
        $this->_vendorPageHelper = $vendorPageHelper;
        $this->_imageHelper = $imageHelper;
        $this->_configHelper = $configHelper;
        $this->_vendorProfile = $vendorProfile;
    }

    /**
     * @return string
     */
    public function getVendorUrl()
    {
        return $this->_vendorPageHelper->getUrl($this->getVendor());
    }

    /**
     * @return bool
     */
    public function isVendorHomePage()
    {
        $vendorUrl = $this->_vendorPageHelper->getUrl($this->getVendor());
        if (!$this->getVendor()->getId() || !$vendorUrl) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getVendorLogoSrc()
    {
        return $this->_vendorProfile->getLogoUrl();
    }

    /**
     * @return string
     */
    public function getVendorStoreName()
    {
        return $this->_vendorHelper->getVendorStoreName($this->getVendor()->getId());
    }

    /**
     * Get Current Vendor.
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('current_vendor');
    }
}
