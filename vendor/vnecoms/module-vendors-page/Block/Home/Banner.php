<?php
namespace Vnecoms\VendorsPage\Block\Home;

use Magento\Framework\App\Filesystem\DirectoryList;

class Banner extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $_fileStorageDatabase;
    
    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected $mediaDirectory;
    
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $_pageHelper;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;
    
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
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDatabase,
        array $data = []
    ) {
        
        $this->_fileStorageDatabase = $fileStorageDatabase;
        $this->_mediaDirectory = $context->getFilesystem()->getDirectoryRead(DirectoryList::MEDIA);
        $this->_pageHelper = $pageHelper;
        $this->_vendorHelper = $vendorHelper;
        $this->_coreRegistry = $registry;
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
     * Get logo Src
     *
     * @return string
     */
    public function getBannerSrc()
    {
        $vendorId = $this->getVendor()->getId();
        $bannerFile = $this->_pageHelper->getVendorBanner($vendorId);
        $path = 'ves_vendors/banner/' . $bannerFile;
        $logoUrl = $this->_storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path;
    
        if ($bannerFile !== null && $this->checkIsFile($path)) {
            return $logoUrl;
        }
    
        /*Use default banner*/
        $defaultBannerFile = $this->_scopeConfig->getValue('vendors/vendorspage/default_banner');
        $path = 'ves_vendors/banner/' . $defaultBannerFile;
        $logoUrl = $this->_storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path;
        
        if ($defaultBannerFile !== null && $this->checkIsFile($path)) {
            return $logoUrl;
        }
        
        /*If default banner is not set*/
        return false;
    }
    
    /**
     * If DB file storage is on - find there, otherwise - just file_exists
     *
     * @param string $filename relative file path
     * @return bool
     */
    protected function checkIsFile($filename)
    {
        if ($this->_fileStorageDatabase->checkDbUsage() && !$this->_mediaDirectory->isFile($filename)) {
            $this->_fileStorageDatabase->saveFileToFilesystem($filename);
        }
        return $this->_mediaDirectory->isFile($filename);
    }
    
    /**
     * Get Store Name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->_vendorHelper->getVendorStoreName($this->getVendor()->getId());
    }
    
    /**
     * (non-PHPdoc)
     * @see \Magento\Framework\View\Element\Template::_toHtml()
     */
    protected function _toHtml()
    {
        if (!$this->getBannerSrc()) {
            return '';
        }
        return parent::_toHtml();
    }
}
