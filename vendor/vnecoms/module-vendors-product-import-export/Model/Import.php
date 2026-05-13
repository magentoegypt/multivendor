<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile
namespace Vnecoms\VendorsProductImportExport\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Math\Random;

/**
 * Import model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 *
 * @method string getBehavior() getBehavior()
 * @method \Magento\ImportExport\Model\Import setEntity() setEntity(string $value)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Import extends \Magento\ImportExport\Model\Import
{
    /**
     * Import field separator.
     */
    const FIELD_SEPARATOR = ',';

    /**
     * Allow multiple values wrapping in double quotes for additional attributes.
     */
    const FIELDS_ENCLOSURE = 'fields_enclosure';

    /**
     * Import multiple value separator.
     */
    const FIELD_MULTIPLE_VALUE_SEPARATOR = ',';

    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $_vendor;


    /**
     * Set Vendor
     *
     * @param \Vnecoms\Vendors\Model\Vendor $vendor
     */
    public function setVendor(\Vnecoms\Vendors\Model\Vendor $vendor){
        $this->_vendor = $vendor;
        return $this;
    }

    /**
     * Get Vendor
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        return $this->_vendor;
    }

    /**
     * Override standard entity getter.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return string
     */
    public function getEntity()
    {
        return 'catalog_product';
    }

    /**
     * Import/Export working directory (source files, result files, lock files etc.).
     *
     * @return string
     */
    public function getWorkingDir()
    {
        return $this->_varDirectory->getAbsolutePath('importexport/'.$this->_vendor->getVendorId().'/');
    }

    /**
     * (non-PHPdoc)
     * @see \Magento\ImportExport\Model\Import::_getEntityAdapter()
     */
    protected function _getEntityAdapter()
    {
        $adapter = parent::_getEntityAdapter();
        if(!$adapter->getVendor()){
            $adapter->setVendor($this->getVendor());
        }

        return $adapter;
    }

    /**
     * Create history report
     *
     * @param string $entity
     * @param string $extension
     * @param string $sourceFileRelative
     * @param array $result
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createHistoryReport($sourceFileRelative, $entity, $extension = null, $result = null)
    {
        /*Don't save history report for vendor*/
        return $this;
    }

    /**
     * Returns source adapter object.
     *
     * @param string $sourceFile Full path to source file
     * @return \Magento\ImportExport\Model\Import\AbstractSource
     */
    protected function _getSourceAdapter($sourceFile)
    {
        return \Vnecoms\VendorsProductImportExport\Model\Import\Adapter::findAdapterFor(
            $sourceFile,
            $this->_filesystem->getDirectoryWrite(DirectoryList::ROOT),
            $this->getData(self::FIELD_FIELD_SEPARATOR)
        );
    }


    /**
     * Move uploaded file.
     *
     * @throws LocalizedException
     * @return string Source file path
     */
    public function uploadSource()
    {
        $adapter = $this->_httpFactory->create();
        if (!$adapter->isValid(self::FIELD_NAME_SOURCE_FILE)) {
            $errors = $adapter->getErrors();
            if ($errors[0] == \Laminas\Validator\File\Upload::INI_SIZE) {
                $errorMessage = $this->_importExportData->getMaxUploadSizeMessage();
            } else {
                $errorMessage = __('The file was not uploaded.');
            }
            throw new LocalizedException($errorMessage);
        }

        $entity = $this->getEntity();
        /** @var $uploader Uploader */
        $uploader = $this->_uploaderFactory->create(['fileId' => self::FIELD_NAME_SOURCE_FILE]);
        $uploader->setAllowedExtensions(['csv', 'zip' ,'xls', 'xlsx']);
        $uploader->skipDbProcessing(true);
        $random = ObjectManager::getInstance()
            ->get(Random::class);
        $fileName = $random->getRandomString(32) . '.' . $uploader->getFileExtension();
        try {
            $result = $uploader->save($this->getWorkingDir(), $fileName);
        } catch (\Exception $e) {
            throw new LocalizedException(__('The file cannot be uploaded.'));
        }

        // phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
        $extension = pathinfo($result['file'], PATHINFO_EXTENSION);

        $uploadedFile = $result['path'] . $result['file'];
        if (!$extension) {
            $this->_varDirectory->delete($uploadedFile);
            throw new LocalizedException(__('The file you uploaded has no extension.'));
        }
        $sourceFile = $this->getWorkingDir() . $entity;

        $sourceFile .= '.' . $extension;
        $sourceFileRelative = $this->_varDirectory->getRelativePath($sourceFile);

        if (strtolower($uploadedFile) != strtolower($sourceFile)) {
            if ($this->_varDirectory->isExist($sourceFileRelative)) {
                $this->_varDirectory->delete($sourceFileRelative);
            }

            try {
                $this->_varDirectory->renameFile(
                    $this->_varDirectory->getRelativePath($uploadedFile),
                    $sourceFileRelative
                );
            } catch (FileSystemException $e) {
                throw new LocalizedException(__('The source file moving process failed.'));
            }
        }
        $this->_removeBom($sourceFile);
        $this->createHistoryReport($sourceFileRelative, $entity, $extension, $result);
        return $sourceFile;
    }
}
