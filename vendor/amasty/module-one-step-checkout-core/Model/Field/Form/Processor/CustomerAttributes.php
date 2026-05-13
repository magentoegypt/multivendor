<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form\Processor;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes\UpdateField;
use Amasty\CheckoutCore\Model\Field\Form\GetCustomerAttributes;
use Amasty\CheckoutCore\Model\Field\Form\SelectFormCodes;
use Amasty\CheckoutCore\Model\Field\SetAttributeFrontendLabel;
use Amasty\CheckoutCore\Model\ModuleEnable;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class CustomerAttributes implements ProcessorInterface
{
    /**
     * The same as \Amasty\CustomerAttributes\Model\CustomerFormManager::ORDER_OFFSET
     *
     * @var int
     */
    public const SORT_ORDER_OFFSET = 1000;

    /**
     * @var ModuleEnable
     */
    private $moduleEnable;

    /**
     * @var GetCustomerAttributes
     */
    private $getCustomerAttributes;

    /**
     * @var SetAttributeFrontendLabel
     */
    private $setAttributeFrontendLabel;

    /**
     * @var SelectFormCodes
     */
    private $selectFormCodes;

    /**
     * @var AttributeResource
     */
    private $attributeResource;

    public function __construct(
        ModuleEnable $moduleEnable,
        GetCustomerAttributes $getCustomerAttributes,
        SetAttributeFrontendLabel $setAttributeFrontendLabel,
        SelectFormCodes $selectFormCodes,
        AttributeResource $attributeResource
    ) {
        $this->moduleEnable = $moduleEnable;
        $this->getCustomerAttributes = $getCustomerAttributes;
        $this->setAttributeFrontendLabel = $setAttributeFrontendLabel;
        $this->selectFormCodes = $selectFormCodes;
        $this->attributeResource = $attributeResource;
    }

    public function process(array $fields, int $storeId): array
    {
        if (!$this->moduleEnable->isCustomerAttributesEnable() || empty($fields)) {
            return $fields;
        }

        $attributes = $this->getCustomerAttributes->execute($storeId);
        if (empty($attributes)) {
            return $fields;
        }

        foreach ($attributes as $attribute) {
            $attributeId = (int) $attribute->getAttributeId();

            if (!isset($fields[$attributeId])) {
                continue;
            }

            $fieldData = $fields[$attributeId];
            if (!empty($fieldData['use_default'])) {
                unset($fields[$attributeId]);
                continue;
            }

            $this->setAttributeFrontendLabel->execute(
                $attribute,
                $storeId,
                $fieldData['label']
            );

            if ($storeId === Field::DEFAULT_STORE_ID) {
                $this->updateAttribute($attribute, $fieldData);
            }

            $this->attributeResource->save($attribute);
            unset($fields[$attributeId]);
        }

        return $fields;
    }

    private function updateAttribute(Attribute $attribute, array $fieldData): void
    {
        $isEnabled = (bool) $fieldData[Field::ENABLED];
        $isRequired = $isEnabled && isset($fieldData['required']);

        $attribute->setData(UpdateField::FLAG_NO_FIELD_UPDATE, true);
        $attribute->setData('sorting_order', $fieldData['sort_order']);
        $attribute->setData('sort_order', $fieldData['sort_order'] + self::SORT_ORDER_OFFSET);
        $attribute->setData('used_in_product_listing', $isEnabled);
        $attribute->setData('used_in_forms', $this->selectFormCodes->execute($attribute, $fieldData));
        $attribute->setData('required_on_front', false);
        $attribute->setIsRequired($isRequired);
    }
}
