<?php

namespace Vnecoms\VendorsConfig\Model\Data;

use Vnecoms\VendorsConfig\Api\Data\DocumentContentValidatorInterface;
use Vnecoms\VendorsConfig\Api\Data\DocumentContentInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;

/**
 * Class DocumentContentValidator
 *
 * @package Ecommage\CustomerAvatar\Model\Data
 */
class DocumentContentValidator implements DocumentContentValidatorInterface
{
    /**
     * @var array
     */
    private $defaultMimeTypes
        = [
            'text/csv',
            'image/jpg',
            'image/jpeg',
            'image/gif',
            'image/png',
            'text/plain',
            'application/zip',
            'application/pdf',
            'application/msword',
            'application/vnd.ms-excel',
            'application/x-rar-compressed',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

    /**
     * @var array
     */
    private $allowedMimeTypes;

    /**
     * @param array $allowedMimeTypes
     *
     * @codingStandardsIgnoreStart
     */
    public function __construct(
        array $allowedMimeTypes = []
    ) {
        $this->allowedMimeTypes = array_merge($this->defaultMimeTypes, $allowedMimeTypes);
    }

    /**
     * Check if gallery entry content is valid
     *
     * @param DocumentContentInterface $documentContent
     *
     * @return bool
     * @throws InputException
     * @codingStandardsIgnoreStart
     */
    public function isValid(DocumentContentInterface $documentContent)
    {
        $fileContent = base64_decode($documentContent->getBase64EncodedData(), true);
        if (empty($fileContent)) {
            throw new InputException(new Phrase('The document content must be valid base64 encoded data.'));
        }
        $sourceMimeType = $documentContent->getType();
        if (!$this->isMimeTypeValid($sourceMimeType)) {
            throw new InputException(new Phrase('The document MIME type is not valid or not supported.'));
        }
        if (!$this->isNameValid($documentContent->getName())) {
            throw new InputException(new Phrase('Provided document name contains forbidden characters.'));
        }
        return true;
    }

    /**
     * Check if given mime type is valid
     *
     * @param string $mimeType
     *
     * @return bool
     */
    protected function isMimeTypeValid($mimeType)
    {
        return in_array($mimeType, $this->allowedMimeTypes);
    }

    /**
     * Check if given filename is valid
     *
     * @param string $name
     *
     * @return bool
     */
    protected function isNameValid($name)
    {
        // Cannot contain \ / : * ? " < > |
        if (!preg_match('/^[^\\/?*:";<>()|{}\\\\]+$/', $name)) {
            return false;
        }
        return true;
    }
}
