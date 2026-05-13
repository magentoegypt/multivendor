<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Wysiwyg\Images;

use Vnecoms\VendorsCms\Helper\Wysiwyg\Images;

/**
 * Wysiwyg Images model.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Storage extends \Magento\Cms\Model\Wysiwyg\Images\Storage
{
    const THUMB_PLACEHOLDER_PATH_SUFFIX = 'Vnecoms_VendorsCms::images/placeholder_thumbnail.jpg';

    const THUMBS_DIRECTORY_NAME = 'thumbs';
    /**
     * Cms wysiwyg images.
     *
     * @var \Vnecoms\VendorsCms\Helper\Wysiwyg\Images
     */
    protected $_cmsWysiwygImages = null;

    /**
     * @var \Vnecoms\Vendors\Model\UrlInterface
     */
    protected $_vendorUrl;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * Storage collection factory.
     *
     * @var \Vnecoms\VendorsCms\Model\Wysiwyg\Images\Storage\CollectionFactory
     */
    protected $_storageCollectionFactory;

    public function __construct(
        \Magento\Backend\Model\Session $session,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        Images $cmsWysiwygImages,
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Cms\Model\Wysiwyg\Images\Storage\CollectionFactory $storageCollectionFactory,
        \Magento\MediaStorage\Model\File\Storage\FileFactory $storageFileFactory,
        \Magento\MediaStorage\Model\File\Storage\DatabaseFactory $storageDatabaseFactory,
        \Magento\MediaStorage\Model\File\Storage\Directory\DatabaseFactory $directoryDatabaseFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Vnecoms\Vendors\Model\UrlInterface $vendorUrl,
        \Vnecoms\VendorsCms\Model\Wysiwyg\Images\Storage\CollectionFactory $cmsStorageCollectionFactory,
        array $resizeParameters = [],
        array $extensions = [],
        array $dirs = [],
        array $data = []
    ) {
        $this->_cmsWysiwygImages = $cmsWysiwygImages;
        $this->_storageCollectionFactory = $storageCollectionFactory;
        parent::__construct($session, $backendUrl, $cmsWysiwygImages, $coreFileStorageDb, $filesystem, $imageFactory, $assetRepo, $storageCollectionFactory, $storageFileFactory, $storageDatabaseFactory, $directoryDatabaseFactory, $uploaderFactory, $resizeParameters, $extensions, $dirs, $data);
        $this->vendorSession = $vendorSession;
        $this->_vendorUrl = $vendorUrl;
        $this->_session = $this->vendorSession;
        $this->_backendUrl = $this->_vendorUrl;
        $this->_storageCollectionFactory = $cmsStorageCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbsPath($filePath = false)
    {
        $mediaRootDir = $this->_cmsWysiwygImages->getVendorRoot();
        $thumbnailDir = $this->getThumbnailRoot();

        if ($filePath && strpos($filePath, $mediaRootDir) === 0) {
            $thumbnailDir .= dirname(substr($filePath, strlen($mediaRootDir)));
        }

        return $thumbnailDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function _validatePath($path)
    {
        $root = $this->_sanitizePath($this->_cmsWysiwygImages->getVendorRoot());
        if ($root == $path) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t delete root directory %1 right now.', $path)
            );
        }
        if (strpos($path, $root) !== 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Directory %1 is not under storage root path.', $path)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnailRoot()
    {
        return $this->_cmsWysiwygImages->getVendorRoot().'/'.self::THUMBS_DIRECTORY_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesCollection($path, $type = null)
    {
        if ($this->_coreFileStorageDb->checkDbUsage()) {
            $files = $this->_storageDatabaseFactory->create()->getDirectoryFiles($path);

            /** @var \Magento\MediaStorage\Model\File\Storage\File $fileStorageModel */
            $fileStorageModel = $this->_storageFileFactory->create();
            foreach ($files as $file) {
                $fileStorageModel->saveFile($file);
            }
        }

        $collection = $this->getCollection(
            $path
        )->setCollectDirs(
            false
        )->setCollectFiles(
            true
        )->setCollectRecursively(
            false
        )->setOrder(
            'mtime',
            \Magento\Framework\Data\Collection::SORT_ORDER_ASC
        );

        // Add files extension filter
        if ($allowed = $this->getAllowedExtensions($type)) {
            $collection->setFilesFilter('/\.('.implode('|', $allowed).')$/i');
        }

        // prepare items
        foreach ($collection as $item) {
            $item->setId($this->_cmsWysiwygImages->idEncode($item->getBasename()));
            $item->setName($item->getBasename());
            $item->setShortName($this->_cmsWysiwygImages->getShortFilename($item->getBasename()));
            $item->setUrl($this->_cmsWysiwygImages->getCurrentUrl().$item->getBasename());

            if ($this->isImage($item->getBasename())) {
                $thumbUrl = $this->getThumbnailUrl($item->getFilename(), true);
                // generate thumbnail "on the fly" if it does not exists
                if (!$thumbUrl) {
                    $thumbUrl = $this->_vendorUrl->getUrl('cms/*/thumbnail', ['file' => $item->getId()]);
                }

                $size = @getimagesize($item->getFilename());

                if (is_array($size)) {
                    $item->setWidth($size[0]);
                    $item->setHeight($size[1]);
                }
            } else {
                $thumbUrl = $this->_assetRepo->getUrl(self::THUMB_PLACEHOLDER_PATH_SUFFIX);
            }

            $item->setThumbUrl($thumbUrl);
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getRelativePathToRoot($path)
    {
        return substr(
            $this->_sanitizePath($path),
            strlen($this->_sanitizePath($this->_cmsWysiwygImages->getVendorRoot()))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnailUrl($filePath, $checkFile = false)
    {
        $mediaRootDir = $this->_cmsWysiwygImages->getVendorRoot();

        if (strpos($filePath, $mediaRootDir) === 0) {
            $thumbSuffix = self::THUMBS_DIRECTORY_NAME.substr($filePath, strlen($mediaRootDir));
            if (!$checkFile || $this->_directory->isExist(
                $this->_directory->getRelativePath($mediaRootDir.'/'.$thumbSuffix)
            )
            ) {
                $thumbSuffix = substr(
                    $mediaRootDir,
                    strlen($this->_directory->getAbsolutePath())
                ).'/'.$thumbSuffix;
                $randomIndex = '?rand='.time();

                return str_replace('\\', '/', $this->_cmsWysiwygImages->getBaseUrl().$thumbSuffix).$randomIndex;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createDirectory($name, $path)
    {
        if (!preg_match(self::DIRECTORY_NAME_REGEXP, $name)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please rename the folder using only letters, numbers, underscores and dashes.')
            );
        }

        $relativePath = $this->_directory->getRelativePath($path);
        if (!$this->_directory->isDirectory($relativePath) || !$this->_directory->isWritable($relativePath)) {
            $path = $this->_cmsWysiwygImages->getVendorRoot();
        }

        $newPath = $path.'/'.$name;
        $relativeNewPath = $this->_directory->getRelativePath($newPath);
        if ($this->_directory->isDirectory($relativeNewPath)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We found a directory with the same name. Please try another folder name.')
            );
        }

        $this->_directory->create($relativeNewPath);
        try {
            if ($this->_coreFileStorageDb->checkDbUsage()) {
                $relativePath = $this->_coreFileStorageDb->getMediaRelativePath($newPath);
                $this->_directoryDatabaseFactory->create()->createRecursive($relativePath);
            }

            $result = [
                'name' => $name,
                'short_name' => $this->_cmsWysiwygImages->getShortFilename($name),
                'path' => $newPath,
                'id' => $this->_cmsWysiwygImages->convertPathToId($newPath),
            ];

            return $result;
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot create a new directory.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDirectory($path)
    {
        if ($this->_coreFileStorageDb->checkDbUsage()) {
            $this->_directoryDatabaseFactory->create()->deleteDirectory($path);
        }
        try {
            $this->_deleteByPath($path);
            $path = $this->getThumbnailRoot().$this->_getRelativePathToRoot($path);
            $this->_deleteByPath($path);
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot delete directory %1.', $path));
        }
    }
}
