<?php

namespace Vnecoms\VendorsConfig\Api\Data;

use Vnecoms\VendorsConfig\Api\Data\DocumentContentInterface;
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
