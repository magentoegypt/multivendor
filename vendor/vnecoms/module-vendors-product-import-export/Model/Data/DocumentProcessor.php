<?php

namespace Vnecoms\VendorsProductImportExport\Model\Data;

use Vnecoms\VendorsProductImportExport\Api\Data\DocumentContentValidatorInterface;
use Vnecoms\VendorsProductImportExport\Api\Data\DocumentProcessorInterface;
use Magento\Framework\Api\CustomAttributesDataInterface;
use Vnecoms\VendorsProductImportExport\Api\Data\DocumentContentInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem;
use Magento\Framework\Phrase;
use Vnecoms\VendorsApi\Model\Data\Uploader;
use Magento\Framework\Api\DataObjectHelper;

/**
 * Class DocumentProcessor
 *
 * @package Ecommage\CustomerAvatar\Model\Data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DocumentProcessor implements DocumentProcessorInterface
{
    /**
     * MIME type/extension map
     *
     * @var array
     */
    protected $mimeTypeExtensionMap
        = [
            'image/jpg'                                                                 => 'jpg',
            'image/jpeg'                                                                => 'jpg',
            'image/gif'                                                                 => 'gif',
            'image/png'                                                                 => 'png'
        ];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Filesystem
     */
    private $contentValidator;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;

    /**
     * DocumentProcessor constructor.
     *
     * @param Filesystem                        $fileSystem
     * @param DocumentContentValidatorInterface $contentValidator
     * @param DataObjectHelper                  $dataObjectHelper
     * @param \Psr\Log\LoggerInterface          $logger
     * @param Uploader                          $uploader
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Filesystem $fileSystem,
        DocumentContentValidatorInterface $contentValidator,
        DataObjectHelper $dataObjectHelper,
        \Psr\Log\LoggerInterface $logger,
        Uploader $uploader
    ) {
        $this->filesystem       = $fileSystem;
        $this->contentValidator = $contentValidator;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->logger           = $logger;
        $this->uploader         = $uploader;
        $this->mediaDirectory   = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * {@inheritdoc}
     * @codingStandardsIgnoreStart
     */
    public function save(
        CustomAttributesDataInterface $dataObjectWithCustomAttributes,
        $entityType,
        CustomAttributesDataInterface $previousCustomerData = null
    ) {
        //Get all Document related custom attributes
        $documentDataObjects = $this->dataObjectHelper->getCustomAttributeValueByType(
            $dataObjectWithCustomAttributes->getCustomAttributes(),
            \Ecommage\CustomerAvatar\Api\Data\DocumentContentInterface::class
        );

        // Return if no documents to process
        if (empty($documentDataObjects)) {
            return $dataObjectWithCustomAttributes;
        }

        // For every document, save it and replace it with corresponding Eav data object
        /** @var $documentDataObject \Magento\Framework\Api\AttributeValue */
        foreach ($documentDataObjects as $documentDataObject) {

            /** @var $documentContent \Ecommage\CustomerAvatar\Api\Data\DocumentContentInterface */
            $documentContent = $documentDataObject->getValue();

            $filename = $this->processDocumentContent($entityType, $documentContent);

            //Set filename from static media location into data object
            $dataObjectWithCustomAttributes->setCustomAttribute(
                $documentDataObject->getAttributeCode(),
                $filename
            );

            //Delete previously saved document if it exists
            if ($previousCustomerData) {
                $previousImageAttribute = $previousCustomerData->getCustomAttribute(
                    $documentDataObject->getAttributeCode()
                );
                if ($previousImageAttribute) {
                    $previousImagePath = $previousImageAttribute->getValue();
                    if (!empty($previousImagePath) && ($previousImagePath != $filename)) {
                        @unlink($this->mediaDirectory->getAbsolutePath() . $entityType . $previousImagePath);
                    }
                }
            }
        }

        return $dataObjectWithCustomAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function processDocumentContent($entityType, $documentContent)
    {
        if (!$this->contentValidator->isValid($documentContent)) {
            throw new InputException(new Phrase('The document content is not valid.'));
        }

        $fileContent  = @base64_decode($documentContent->getBase64EncodedData(), true);
        $tmpDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);
        $fileName     = $this->getFileName($documentContent);
        $tmpFileName  = substr(md5(rand()), 0, 7) . '.' . $fileName;
        $tmpDirectory->writeFile($tmpFileName, $fileContent);

        $fileAttributes = [
            'tmp_name' => $tmpDirectory->getAbsolutePath() . $tmpFileName,
            'name'     => $documentContent->getName()
        ];

        try {
            $this->uploader->processFileAttributes($fileAttributes);
            $this->uploader->setFilesDispersion(false);
            $this->uploader->setFilenamesCaseSensitivity(false);
            $this->uploader->setAllowRenameFiles(true);
            $destinationFolder = $entityType;
            $result = $this->uploader->save($this->mediaDirectory->getAbsolutePath($destinationFolder), $fileName);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        return $result;
    }

    /**
     * @param string $mimeType
     *
     * @return string
     */
    protected function getMimeTypeExtension($mimeType)
    {
        return isset($this->mimeTypeExtensionMap[$mimeType]) ? $this->mimeTypeExtensionMap[$mimeType] : '';
    }

    /**
     * @param DocumentContentInterface $documentContent
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getFileName($documentContent)
    {
        $fileName = $documentContent->getName();
        if (!pathinfo($fileName, PATHINFO_EXTENSION)) {
            if (!$documentContent->getType() || !$this->getMimeTypeExtension($documentContent->getType())) {
                throw new InputException(new Phrase('Cannot recognize document extension.'));
            }
            $fileName .= '.' . $this->getMimeTypeExtension($documentContent->getType());
        }
        return $fileName;
    }
}
