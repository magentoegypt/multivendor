<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Helper\Wysiwyg;

/**
 * Wysiwyg Images Helper.
 */
class Images extends \Magento\Cms\Helper\Wysiwyg\Images
{
    /** @var \Magento\Framework\Registry  */
    protected $_coreRegistry;

    protected $vendorSession;

    /** @var \Vnecoms\Vendors\Model\UrlInterface  */
    protected $_vendorUrl;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\Vendors\Model\UrlInterface $vendorUrl
    ) {
        parent::__construct($context, $backendData, $filesystem, $storeManager, $escaper);
        $this->_coreRegistry = $coreRegistry;
        $this->_vendorUrl = $vendorUrl;
    }

    /**
     * @param $filename
     * @param bool $renderAsTag
     * @param bool $forceStaticPath
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getImageHtmlDeclaration($filename, $renderAsTag = false, $forceStaticPath = false)
    {
        $fileUrl = $this->getCurrentUrl() . $filename;
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $mediaPath = str_replace($mediaUrl, '', $fileUrl);
        $directive = sprintf('{{media url="%s"}}', $mediaPath);
        if ($renderAsTag) {

            $src = $this->isUsingStaticUrlsAllowed() || $forceStaticPath ? $fileUrl : $this->escaper->escapeHtml($directive);
            $html = sprintf('<img src="%s" alt="" />', $src);
        } else {
            if ($this->isUsingStaticUrlsAllowed() || $forceStaticPath) {
                $html = $fileUrl;
            } else {
                $directive = $this->urlEncoder->encode($directive);
                $html = $this->_backendData->getUrl(
                    'cms/wysiwyg/directive',
                    [
                        '___directive' => $directive,
                        '_escape_params' => false,
                    ]
                );
            }
        }
        return $html;
    }

    /**
     * @return string
     */
    public function getCurrentUrl()
    {
        if (!$this->_currentUrl) {
            $path = $this->getCurrentPath();
            $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
            $this->_currentUrl = $mediaUrl.$this->_directory->getRelativePath($path).'/';
        }

        return $this->_currentUrl;
    }

    /**
     * @return string
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentPath()
    {
        $vid = $this->getVendorId();
        if (!$this->_currentPath && $vid) {
            $currentPath = $this->_directory->getAbsolutePath().\Vnecoms\VendorsCms\Model\Wysiwyg\Config::IMAGE_VENDORSCMS_DIRECTORY.'/'.$vid;
            $path = $this->_getRequest()->getParam($this->getTreeNodeName());
            if ($path) {
                $path = $this->convertIdToPath($path);
                if ($this->_directory->isDirectory($this->_directory->getRelativePath($path))) {
                    $currentPath = $path;
                }
            }
            try {
                $currentDir = $this->_directory->getRelativePath($currentPath);
                if (!$this->_directory->isExist($currentDir)) {
                    $this->_directory->create($currentDir);
                }
            } catch (\Magento\Framework\Exception\FileSystemException $e) {
                $message = __('The directory %1 is not writable by server.', $currentPath);
                throw new \Magento\Framework\Exception\LocalizedException($message);
            }
            $this->_currentPath = $currentPath;
        }

        return $this->_currentPath;
    }

    /**
     * @return string
     */
    public function getVendorRoot()
    {
        return $this->_directory->getAbsolutePath(\Vnecoms\VendorsCms\Model\Wysiwyg\Config::IMAGE_VENDORSCMS_DIRECTORY.'/'.$this->getVendorId());
    }

    /**
     * Encode path to HTML element id.
     *
     * @param string $path Path to file/directory
     *
     * @return string
     */
    public function convertPathToId($path)
    {
        $path = str_replace($this->getVendorRoot(), '', $path);

        return $this->idEncode($path);
    }

    /**
     * Decode HTML element id.
     *
     * @param string $id
     *
     * @return string
     */
    public function convertIdToPath($id)
    {
        if ($id === \Magento\Theme\Helper\Storage::NODE_ROOT) {
            return $this->getVendorRoot();
        } else {
            return $this->getVendorRoot().$this->idDecode($id);
        }
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->vendorSession == null) {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\Vendors\Model\Session');
        }

        return $this->vendorSession->getVendor();
    }

    /**
     * Get vendorIdentifi ex: vendor1.
     *
     * @param void
     *
     * @return string
     */
    public function getVendorId()
    {
        if (!$this->getVendor()->getId()) {
            $vendor = \Magento\Framework\App\ObjectManager::getInstance()->get('Vnecoms\Vendors\Model\Vendor');
            $vendorId = $vendor->getVendorId();

            return $vendorId;
        }

        return $this->getVendor()->getVendorId();
    }
}
