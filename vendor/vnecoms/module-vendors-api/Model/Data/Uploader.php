<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsApi\Model\Data;

/**
 * Class Uploader specific to uploading images using services
 */
class Uploader extends \Magento\Framework\File\Uploader
{
    /**
     * Core file storage
     *
     * @var \Magento\MediaStorage\Helper\File\Storage
     */
    protected $_coreFileStorage = null;

    /**
     * Core file storage database
     *
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $_coreFileStorageDb = null;

    /**
     * Uploader constructor.
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb
     * @param \Magento\MediaStorage\Helper\File\Storage $coreFileStorage
     * @param \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator
     */
    public function __construct(
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb,
        \Magento\MediaStorage\Helper\File\Storage $coreFileStorage,
        \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator
    ) {
        $this->_coreFileStorageDb = $coreFileStorageDb;
        $this->_coreFileStorage = $coreFileStorage;
    }

    /**
     * Save file to storage
     *
     * @param  array $result
     * @return \Magento\MediaStorage\Model\File\Uploader
     */
    protected function _afterSave($result)
    {
        if (empty($result['path']) || empty($result['file'])) {
            return $this;
        }

        if ($this->_coreFileStorage->isInternalStorage()) {
            return $this;
        }

        $this->_result['file'] = $this->_coreFileStorageDb->saveUploadedFile($result);

        return $this;
    }

    /**
     * Explicitly set the file attributes instead of setting it via constructor
     *
     * @param array $fileAttributes
     * @return void
     * @throws \Exception
     */
    public function processFileAttributes($fileAttributes)
    {
        $this->_file = $fileAttributes;
        if (!file_exists($this->_file['tmp_name'])) {
            $code = empty($this->_file['tmp_name']) ? self::TMP_NAME_EMPTY : 0;
            throw new \Exception('File was not processed correctly.', $code);
        } else {
            $this->_fileExists = true;
        }
    }
}
