<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form\Processor;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\DeleteAttributeFrontendLabel;
use Amasty\CheckoutCore\Model\Field\Form\Processor\FieldsByStore\CanUseDefaultField;
use Amasty\CheckoutCore\Model\FieldFactory;
use Amasty\CheckoutCore\Model\Field\Form\SaveField;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Amasty\CheckoutCore\Model\ResourceModel\Field\CollectionFactory;
use Magento\Framework\App\ObjectManager;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class FieldsByStore implements ProcessorInterface
{
    /**
     * @var CollectionFactory
     */
    private $fieldCollectionFactory;

    /**
     * @var SaveField
     */
    private $saveField;

    /**
     * @var FieldFactory
     */
    private $fieldFactory;

    /**
     * @var FieldResource
     */
    private $fieldResource;

    /**
     * @var CanUseDefaultField
     */
    private $canUseDefaultField;

    /**
     * @var DeleteAttributeFrontendLabel|null
     */
    private $deleteAttributeFrontendLabel;

    public function __construct(
        CollectionFactory $fieldCollectionFactory,
        SaveField $saveField,
        FieldFactory $fieldFactory,
        FieldResource $fieldResource,
        CanUseDefaultField $canUseDefaultField,
        DeleteAttributeFrontendLabel $deleteAttributeFrontendLabel = null // TODO move to not optional
    ) {
        $this->fieldCollectionFactory = $fieldCollectionFactory;
        $this->saveField = $saveField;
        $this->fieldFactory = $fieldFactory;
        $this->fieldResource = $fieldResource;
        $this->canUseDefaultField = $canUseDefaultField;
        $this->deleteAttributeFrontendLabel = $deleteAttributeFrontendLabel
            ?? ObjectManager::getInstance()->get(DeleteAttributeFrontendLabel::class);
    }

    public function process(array $fields, int $storeId): array
    {
        if (empty($fields)) {
            return [];
        }

        if ($storeId === Field::DEFAULT_STORE_ID) {
            return $fields;
        }

        $fieldCollection = $this->fieldCollectionFactory->create();
        $fieldCollection->addFilterByStoreId($storeId);

        $defaultFieldCollection = $this->fieldCollectionFactory->create();
        $defaultFieldCollection->addFilterByStoreId(Field::DEFAULT_STORE_ID);

        foreach ($fields as $attributeId => $fieldData) {
            $field = $fieldCollection->getItemByColumnValue(Field::ATTRIBUTE_ID, (string) $attributeId);
            $defaultField = $defaultFieldCollection->getItemByColumnValue(
                Field::ATTRIBUTE_ID,
                (string) $attributeId
            );

            $this->processFieldData($field, $defaultField, $fieldData, $attributeId, $storeId);
            unset($fields[$attributeId]);
        }

        return $fields;
    }

    /**
     * @param Field|null $field
     * @param Field $defaultField
     * @param array $fieldData
     * @param int $attributeId
     * @param int $storeId
     * @throws \Exception
     */
    private function processFieldData(
        ?Field $field,
        Field $defaultField,
        array $fieldData,
        int $attributeId,
        int $storeId
    ): void {
        if (!$this->canUseDefaultField->execute($field, $defaultField, $fieldData)) {
            if (!$field) {
                $field = $this->fieldFactory->create();
            }

            $this->saveField->execute(
                $field,
                $this->prepareFieldData($fieldData, $attributeId, $storeId)
            );

            return;
        }

        if ($field) {
            $this->fieldResource->delete($field);
            $this->deleteAttributeFrontendLabel->execute($attributeId, $storeId);
        }
    }

    private function prepareFieldData(array $fieldData, int $attributeId, int $storeId): array
    {
        $isEnabled = (int) $fieldData[Field::ENABLED] === 1;
        $fieldData[Field::REQUIRED] = $isEnabled && isset($fieldData[Field::REQUIRED]);
        $fieldData[Field::ATTRIBUTE_ID] = $fieldData[Field::ATTRIBUTE_ID] ?? $attributeId;
        $fieldData[Field::STORE_ID] = $storeId;
        return $fieldData;
    }
}
