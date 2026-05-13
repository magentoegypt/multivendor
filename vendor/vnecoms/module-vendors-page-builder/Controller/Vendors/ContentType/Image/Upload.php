<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPageBuilder\Controller\Vendors\ContentType\Image;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem;
use Vnecoms\Vendors\App\Action\Context;
use Vnecoms\Vendors\Controller\Vendors\Action;

/**
 * Image upload controller
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Upload extends Action implements HttpPostActionInterface
{
    const UPLOAD_DIR = 'wysiwyg';

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     * @deprecad use $mediaDirectory instead
     */
    private $directoryList;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\File\UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Cms\Helper\Wysiwyg\Images
     */
    private $cmsWysiwygImages;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;

    /**
     * Upload constructor.
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\File\UploaderFactory $uploaderFactory
     * @param Filesystem\DirectoryList $directoryList
     * @param \Vnecoms\VendorsCms\Helper\Wysiwyg\Images $cmsWysiwygImages
     * @param Filesystem|null $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Vnecoms\VendorsCms\Helper\Wysiwyg\Images $cmsWysiwygImages,
        Filesystem $filesystem = null
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->uploaderFactory = $uploaderFactory;
        $this->directoryList = $directoryList;
        $this->cmsWysiwygImages = $cmsWysiwygImages;
        $filesystem = $filesystem ?? ObjectManager::getInstance()->create(Filesystem::class);
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * Retrieve path
     *
     * @param string $path
     * @param string $imageName
     * @return string
     */
    private function getFilePath($path, $imageName)
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }

    /**
     * Allow users to upload images to the folder structure
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $fieldName = $this->getRequest()->getParam('param_name');
        $fileUploader = $this->uploaderFactory->create(['fileId' => $fieldName]);

        // Set our parameters
        $fileUploader->setFilesDispersion(false);
        $fileUploader->setAllowRenameFiles(true);
        $fileUploader->setAllowedExtensions(['jpeg','jpg','png','gif']);
        $fileUploader->setAllowCreateFolders(true);

        try {
            if (!$fileUploader->checkMimeType(['image/png', 'image/jpeg', 'image/gif'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('File validation failed.'));
            }

            $result = $fileUploader->save($this->getUploadDir());
            $baseUrl = $this->_backendUrl->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]);
            $result['id'] = $this->cmsWysiwygImages->idEncode($result['file']);
            $result['url'] = $baseUrl . $this->getFilePath(\Vnecoms\VendorsCms\Model\Wysiwyg\Config::IMAGE_VENDORSCMS_DIRECTORY.'/'.$this->_getSession()->getVendor()->getVendorId(), $result['file']);
        } catch (\Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ];
        }
        return $this->resultJsonFactory->create()->setData($result);
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        return $this->mediaDirectory->getAbsolutePath(
            \Vnecoms\VendorsCms\Model\Wysiwyg\Config::IMAGE_VENDORSCMS_DIRECTORY.'/'.$this->_getSession()->getVendor()->getVendorId());
    }

}
