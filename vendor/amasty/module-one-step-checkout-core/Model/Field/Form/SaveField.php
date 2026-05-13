<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes\UpdateAttributeFromField;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\UpdateConfig;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Amasty\CheckoutCore\Model\ResourceModel\GetCustomerAddressAttributeById;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class SaveField
{
    /**
     * @var FieldResource
     */
    private $fieldResource;

    /**
     * @var GetCustomerAddressAttributeById
     */
    private $getCustomerAddressAttributeById;

    /**
     * @var UpdateConfig
     */
    private $updateConfig;

    /**
     * @var UpdateAttributeFromField
     */
    private $updateAttributeFromField;

    /**
     * @var ProcessCustomFieldAttribute
     */
    private $processCustomFieldAttribute;

    /**
     * @var GetAllowedKeys
     */
    private $getAllowedKeys;

    public function __construct(
        FieldResource $fieldResource,
        GetCustomerAddressAttributeById $getCustomerAddressAttributeById,
        UpdateConfig $updateConfig,
        UpdateAttributeFromField $updateAttributeFromField,
        ProcessCustomFieldAttribute $processCustomFieldAttribute,
        GetAllowedKeys $getAllowedKeys
    ) {
        $this->fieldResource = $fieldResource;
        $this->getCustomerAddressAttributeById = $getCustomerAddressAttributeById;
        $this->updateConfig = $updateConfig;
        $this->updateAttributeFromField = $updateAttributeFromField;
        $this->processCustomFieldAttribute = $processCustomFieldAttribute;
        $this->getAllowedKeys = $getAllowedKeys;
    }

    /**
     * @param Field $field
     * @param array $fieldData
     * @throws AlreadyExistsException
     * @throws \UnexpectedValueException
     * @SuppressWarnings(PHPMD.MissingImport)
     */
    public function execute(Field $field, array $fieldData): void
    {
        $allowedKeys = $this->getAllowedKeys->execute($fieldData);
        if (empty($allowedKeys)) {
            throw new \UnexpectedValueException('No keys were allowed');
        }

        if (empty($fieldData)) {
            return;
        }

        $field->addData(array_intersect_key($fieldData, array_flip($allowedKeys)));
        $this->fieldResource->save($field);

        if ($field->getStoreId() === Field::DEFAULT_STORE_ID) {
            $this->updateConfig->execute($field);

            $attribute = $this->getCustomerAddressAttributeById->execute($field->getAttributeId());
            if ($attribute) {
                $this->updateAttributeFromField->execute($field, $attribute);
            }
        }

        $this->processCustomFieldAttribute->execute($field);
    }
}
