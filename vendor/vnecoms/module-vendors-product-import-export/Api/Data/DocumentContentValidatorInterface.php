<?php

namespace Vnecoms\VendorsProductImportExport\Api\Data;

use Vnecoms\VendorsProductImportExport\Api\Data\DocumentContentInterface;
use Magento\Framework\Exception\InputException;

/**
 * Document content validation interface
 *
 * @api
 */
interface DocumentContentValidatorInterface
{
    /**
     * Check if gallery entry content is valid
     *
     * @param DocumentContentInterface $documentContent
     * @return bool
     * @throws InputException
     */
    public function isValid(DocumentContentInterface $documentContent);
}
