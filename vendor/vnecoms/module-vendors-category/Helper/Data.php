<?php

namespace Vnecoms\VendorsCategory\Helper;

use Magento\Framework\App\Helper\Context as Context;
use Magento\Store\Model\Store;

/**
 * Class Data.
 *
 * @author Vnecoms team <vnecoms.com>
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * @var \Magento\Framework\View\Asset\Source
     */
    protected $assetSource;

    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Backend\Model\UrlInterface $urlInterface,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\View\Asset\Repository $repository,
        \Magento\Framework\View\Asset\Source $assetSource
    ) {
        $this->_backendUrl = $urlInterface;
        $this->_storeManager = $storeManagerInterface;
        $this->fileSystem = $fileSystem;
        $this->assetRepo = $repository;
        $this->assetSource = $assetSource;

        parent::__construct($context);
    }

    /**
     * get Base Url Media.
     *
     * @param string $path   [description]
     * @param bool   $secure [description]
     *
     * @return string [description]
     */
    public function getBaseUrlMedia($path = '', $secure = false)
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, $secure).$path;
    }

    /**
     * @param string $route
     * @param array  $params
     *
     * @return string
     */
    public function getBackendUrl($route = '', $params = ['_current' => true])
    {
        return $this->_backendUrl->getUrl($route, $params);
    }

    /**
     * @param string $path
     * @param bool   $secure
     *
     * @return string
     */
    public function getBaseUrl($path = '', $secure = false)
    {
        return $this->_storeManager->getStore()->getBaseUrl().$path;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getBaseDirMedia($path = '')
    {
        /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
        $mediaDirectory = $this->fileSystem
            ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $path = $mediaDirectory->getAbsolutePath($path);

        return $path;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getEnabledVendorSubCat($storeId = null)
    {
        return $this->scopeConfig->getValue('vendors/category/enabled_vendor_subcat', \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $storeId);
    }
}
