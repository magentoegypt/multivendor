<?php

namespace Vnecoms\BannerManager\Block;

use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime as StdlibDateTime;

/**
 * Class Banner render banner in storefront.
 * 
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Banner extends \Magento\Framework\View\Element\Template
{

    /** @var Manager */
    protected $_moduleManager;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * stdlib timezone.
     *
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_stdTimezone;

    /** @var \Vnecoms\BannerManager\Model\ResourceModel\Banner\CollectionFactory */
    protected $_bannerCollectionFactory;

    /**
     * slider factory.
     *
     * @var \Vnecoms\BannerManager\Model\BannerFactory
     */
    protected $_bannerFactory;

    /** @var */
    protected $_banner;

    /** @var \Vnecoms\BannerManager\Model\ItemFactory */
    protected $_itemFactory;

    /** @var \Vnecoms\BannerManager\Helper\Data */
    public $_bannerHelper;

    protected $_identifier;

    /** @var \Vnecoms\VendorsPage\Helper\Data */
    protected $_vendorPageHelper;

    /** @var  \Vnecoms\Vendors\Model\Vendor */
    protected $vendorFactory;

    /** @var Registry */
    protected $_coreRegistry;

    /**
     * Banner constructor.
     * @param Template\Context $context
     * @param \Vnecoms\VendorsPage\Helper\Data $pageHelper
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDatabase
     * @param \Vnecoms\BannerManager\Model\BannerFactory $bannerFactory
     * @param \Vnecoms\BannerManager\Model\ResourceModel\Banner\CollectionFactory $bannerCollectionFactory
     * @param \Vnecoms\BannerManager\Model\ItemFactory $itemFactory
     * @param Registry $coreRegistry
     * @param StdlibDateTime\Timezone $stdTimezone
     * @param DateTime $dateTime
     * @param \Vnecoms\BannerManager\Helper\Data $helperData
     * @param \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDatabase,
        \Vnecoms\BannerManager\Model\BannerFactory $bannerFactory,
        \Vnecoms\BannerManager\Model\ResourceModel\Banner\CollectionFactory $bannerCollectionFactory,
        \Vnecoms\BannerManager\Model\ItemFactory $itemFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone,
        DateTime $dateTime,
        \Vnecoms\BannerManager\Helper\Data $helperData,
        \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        Manager $moduleManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_bannerFactory = $bannerFactory;
        $this->_bannerCollectionFactory = $bannerCollectionFactory;
        $this->_itemFactory = $itemFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_storeManager = $context->getStoreManager();
        $this->vendorFactory = $vendorFactory;
        $this->_stdTimezone = $stdTimezone;
        $this->dateTime = $dateTime;
        $this->_moduleManager = $moduleManager;
        $this->_bannerHelper = $helperData;
        $this->_vendorPageHelper = $vendorPageHelper;
    }

    /**
     * @return \Vnecoms\BannerManager\Helper\Data
     */
    public function getBannerHelper()
    {
        return $this->_bannerHelper;
    }

    /**
     * Overriden
     * Prepare Content HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $banner = $this->getBanner();
        if (!$banner) return '';

        if (!$banner->isActive()) return '';

        if (!$this->isAvailableBanner($banner)) return '';

        if ($banner->getVendorId()  != $this->getVendor()->getId()) return '';

        $vendorId = $this->getVendorId();
        $banner = $this->getBanner();
        $template = $banner->getBannerTemplate();

        if ($vendorId) {
            $this->setData('vendor_id', $vendorId);
        }
        // Set template of original banner
        $this->setTemplate('Vnecoms_BannerManager::app/bannermanager/' . $template);
        return parent::_toHtml();
    }

    public function isModuleVendorsCmsEnabled()
    {
        return $this->_moduleManager->isEnabled('Vnecoms_VendorsCms');
    }

    /**
     * @param $identifier
     */
    public function setBannerIdentifier($identifier)
    {
        if ($identifier) {
            $this->_identifier = $identifier;

        }
    }

    public function getBannerTemplate()
    {
        $bannerId = $this->getBannerId();
        return $this->_bannerFactory->create()->load($bannerId)->getBannerTemplate();
    }

    /**
     * @return string
     */
    public function getBannerInitParams()
    {
        return json_encode($this->getParams());
    }

    /**
     * Get init params
     *
     * @return array
     */
    public function getParams()
    {
        $banner = $this->getBanner();

        return [
            'effect' => $banner->getBannerEffect(),
            'autoplay' => $banner->getBannerDelay(),
            'speed' => $banner->getBannerSpeed(),
            'template' => $banner->getBannerTemplate(),
            'hidePagination' => $banner->getHidePagination(),
            'pagination_type' => $banner->getTypePagination(),
        ];
    }

    /**
     * @return \Vnecoms\BannerManager\Model\Banner|bool
     */
    public function getBanner()
    {
        $vendorId = $this->getVendor()->getId();
        $bannerId = $this->getData('banner_id');

        if (!$bannerId) return false;

        $this->setData('banner_id', $bannerId);

        if (!$this->_banner) {
            $banner = $this->_bannerFactory->create();
            $banner->load($bannerId);
            if (!$banner->getId()) {
                $banner->load($bannerId, 'identifier');
            }
            if (!$banner->getId()) return false;
            $this->_banner = $banner;
        }

        return $this->_banner;
    }

    /**
     * Get slide item image url
     *
     * @param $path
     * @return string
     */
    public function getImageUrl($path)
    {
        return $this->_bannerHelper->getBaseUrlMedia($path, false);
    }

    /**
     * Return identifiers for produced content.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [\Vnecoms\BannerManager\Model\Banner::CACHE_TAG . '_' . $this->getBannerId()];
    }

    public function getBannerId()
    {
        return $this->getData('banner_id');
    }

    public function setBannerId($bannerId)
    {
        return $this->setData('banner_id', $bannerId);
    }

    /**
     * get from date timestamp
     *
     * @return string|null
     */
    public function getFromDate()
    {
        return strtotime($this->getBanner()->getFromDate());
    }

    /**
     * get to date timestamp
     *
     * @return string|null
     */
    public function getToDate()
    {
        return strtotime($this->getBanner()->getToDate());
    }

    public function getCurrentDate()
    {
        $currentDate = $this->_stdTimezone->date()->format('m/d/y H:i:s');
        return strtotime($currentDate);
    }

    /**
     * Get items collection by banner id
     *
     * @param $bannerId
     * @return array
     */
    public function getItemsByBannerId($bannerId)
    {
        $itemCollection = $this->_itemFactory->create()->getItemsFromBannerId($bannerId);
        return $itemCollection;
    }

    /**
     * Retrieve current vendor id
     *
     * @return object
     */
    public function getVendor()
    {
		$vendor = $this->_coreRegistry->registry('vendor');
        if (!$vendor && $product = $this->_coreRegistry->registry('product')) {
            if ($vendorId = $product->getVendorId()) {
                $vendor = $this->vendorFactory->create()->load($vendorId);
            }
        }
        return $vendor;
    }

    /**
     * Retrieve current vendor id
     *
     * @return int
     */
    public function getVendorId()
    {
        $vendor = $this->_coreRegistry->registry('vendor');
		if($vendor && $vendor->getId()) return $vendor->getId();
		
        if (!$vendor && $product = $this->_coreRegistry->registry('product')) {
            return $product->getVendorId();
        }
		return 0;
    }

    /**
     * @param \Vnecoms\BannerManager\Model\Banner $banner
     * @return bool
     */
    public function isAvailableBanner($banner)
    {
        if  ($banner->getToDate() == null && $banner->getFromDate() == null) {
            return true;
        }
        
        $fromDate = strtotime($banner->getFromDate());
        $toDate = strtotime($banner->getToDate());
        $currentDate = $this->getCurrentDate();

        if ($currentDate < $fromDate || $currentDate > $toDate) {
            return false;
        }


        if ($banner->getFromDate() &&
            $fromDate <= $currentDate &&
            $banner->getToDate() == null ) {
            return true;
        }

        if ($banner->getToDate() &&
            $toDate >= $currentDate &&
            $banner->getFromDate() == null ) {
            return true;
        }

        return true;
    }
}
