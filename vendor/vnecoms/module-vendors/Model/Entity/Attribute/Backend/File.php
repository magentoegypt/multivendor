<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Vendors\Model\Entity\Attribute\Backend;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Validation\ValidationException;

class File extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    /**
     * File uploader
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;

    /**
     * @var Filesystem
     */
    protected $_fileSystem;

    /**
     * File constructor.
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
     * @param Filesystem $fileSystem
     */
    public function __construct(
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        Filesystem $fileSystem
    ) {
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_fileSystem = $fileSystem;
    }

    /**
     * Special processing before attribute save:
     * a) check some rules for password
     * b) transform temporary attribute 'password' into real attribute 'password_hash'
     */
    public function beforeSave($object)
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $oldData = $object->getData($attributeCode);
        try {
            $uploader = $this->_fileUploaderFactory->create(['fileId' => 'vendor_data['.$attributeCode.']']);

            $file = $uploader->validateFile();

            if (isset($file['name']) && $file['name']) {
                if (!file_exists($file['tmp_name'])) {
                    return;
                }
                /* Starting upload */
                $allowedExtensions = str_replace(" ", "", $this->getAttribute()->getDefaultValue());
                if ($allowedExtensions) {
                    $allowedExtensions = explode(",", $allowedExtensions);
                    $uploader->setAllowedExtensions($allowedExtensions);
                }
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $path = $this->_fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('ves_vendors/attribute/'.$attributeCode);
                $result = $uploader->save($path);
                $uploadedFilePath = "ves_vendors/attribute/".$attributeCode.'/'.$result['file'];
                $object->setData($attributeCode, $uploadedFilePath);
            } else {
                $data = $object->getData($attributeCode);
                if (isset($data['delete']) && $data['delete']) {
                    $object->setData($attributeCode, '');
                    //@unlink($this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($data[0]));
                }
            }
        }
        catch (ValidationException $e) {
            $data = $object->getData($attributeCode);
            if (isset($data['delete']) && $data['delete']) {
                $object->setData($attributeCode, '');
            }
            $attribute = $this->getAttribute();
            $label = $attribute->getFrontend()->getLabel();
            throw new LocalizedException(
                __('The "%1" attribute value is wrong. Set the attribute and try again: '.$e->getMessage(), $label)
            );
        }
        catch (\InvalidArgumentException $e) {
            $data = $object->getData($attributeCode);
            if (isset($data['delete']) && $data['delete']) {
                $object->setData($attributeCode, '');
            }
            $attribute = $this->getAttribute();
            $label = $attribute->getFrontend()->getLabel();
            throw new LocalizedException(
                __('The "%1" attribute value is wrong. Set the attribute and try again: '.$e->getMessage(), $label)
            );
        } catch (\Exception $e) {
            $data = $object->getData($attributeCode);
            if (isset($data['delete']) && $data['delete']) {
                $object->setData($attributeCode, '');
            }

        }
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @return bool
     */
    public function validate($object)
    {
        return true;
    }
}
