<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Block\Import;

use Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Framework\UrlInterface;

/**
 * Upload image content block
 */
class Media extends \Magento\Framework\View\Element\Template
{
    /**
     * Block's template
     *
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsProductImportExport::import/media.phtml';

    /**
     * @var array
     */
    protected $jsLayout;


    /**
     * @var array|\Magento\Checkout\Block\Checkout\LayoutProcessorInterface[]
     */
    protected $layoutProcessors;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;

    /**
     * @var \Vnecoms\VendorsProductImportExport\Helper\Data
     */
    protected $_importHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Vendors\Model\Session $session,
        \Vnecoms\VendorsProductImportExport\Helper\Data $importHelper,
        array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->layoutProcessors = $layoutProcessors;
        $this->_vendorSession = $session;
        $this->_importHelper = $importHelper;
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        $this->jsLayout['components']['media']['allowedExtensions'] = $this->getAllowedExtensions();
        $this->jsLayout['components']['media']['maxFileSize'] = false; /*Byes*/
        $this->jsLayout['components']['media']['enableLog'] = false;
        $this->jsLayout['components']['media']['images'] = $this->getUploadedImages();
        $this->jsLayout['components']['media']['delete_url'] = $this->getUrl('catalog/import_media/delete');

        foreach ($this->layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }
        return \Laminas\Json\Json::encode($this->jsLayout);
    }


    /**
     * Get uploaded images of current vendor
     *
     * @return multitype:multitype:string NULL
     */
    public function getUploadedImages()
    {
        /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $mediaDirectory = $om->get('Magento\Framework\Filesystem')
            ->getDirectoryRead(DirectoryList::MEDIA);

        $path = 'vnecoms_import/'.$this->_vendorSession->getVendor()->getVendorId();
        $destinationFolder = $mediaDirectory->getAbsolutePath($path);
        $this->_createDestinationFolder($destinationFolder);
        $dir = new \DirectoryIterator($destinationFolder);
        $images = [];

        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $fileName = $fileinfo->getFilename();
                $fileName = ltrim($fileName, "/");
                $images[$fileName] = [
                    'name' => $fileName,
                    'file' => $fileName,
                    'size' => $fileinfo->getSize(),
                    'type' => $fileinfo->getType(),
                    'url' => $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path.'/' . $fileName,
                    'last_modify' => $this->formatDate(date('Y-m-d h:i:s', $fileinfo->getMTime()), \IntlDateFormatter::SHORT, true),
                ];
            }
        }

        return $images;
    }

    private function _createDestinationFolder($destinationFolder)
    {
        if (!$destinationFolder) {
            return $this;
        }

        if (substr($destinationFolder, -1) == '/') {
            $destinationFolder = substr($destinationFolder, 0, -1);
        }

        if (!(@is_dir($destinationFolder)
            || @mkdir($destinationFolder, 0777, true)
        )) {
            throw new \Exception("Unable to create directory '{$destinationFolder}'.");
        }
        return $this;
    }

    /**
     * Get Allowed Extensions
     *
     * @return Ambigous <\Vnecoms\VendorsProductImportExport\Helper\multitype:string, multitype:string >
     */
    public function getAllowedExtensions()
    {
        return $this->_importHelper->getAllowedExtensions();
    }
    /**
     * @return string
     */
    public function getUploadUrl()
    {
        return $this->getUrl('catalog/import_media/upload');
    }
}
