<?php

namespace Vnecoms\VendorsConfig\Api\Data;

use Magento\Framework\Api\CustomAttributesDataInterface;
use Vnecoms\VendorsConfig\Api\Data\DocumentContentInterface;
use Magento\Framework\Exception\InputException;

/**
 * Interface DocumentProcessorInterface
 *
 * @api
 */
interface DocumentProcessorInterface
{
    /**
     * Process Data objects with document type custom attributes and update the custom attribute values with saved document
     * paths
     *
     * @param CustomAttributesDataInterface $dataObjectWithCustomAttributes
     * @param string                        $entityType entity type
     * @param CustomAttributesDataInterface $previousCustomerData
     *
     * @return CustomAttributesDataInterface
     * @api
     */
    public function save(
        CustomAttributesDataInterface $dataObjectWithCustomAttributes,
        $entityType,
        CustomAttributesDataInterface $previousCustomerData = null
    );

    /**
     * Process document and save it to the entity's documents directory
     *
     * @param string                   $entityType
     * @param DocumentContentInterface $documentContent
     *
     * @return string Relative path of the file where document was saved
     * @throws InputException
     */
    public function processDocumentContent($entityType, $documentContent);
}
