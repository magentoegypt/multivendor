<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form\Processor;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\Form\GetOrderAttributes;
use Amasty\CheckoutCore\Model\Field\SetAttributeFrontendLabel;
use Amasty\CheckoutCore\Model\ModuleEnable;
use Amasty\CheckoutCore\Observer\OrderAttribute\InvalidateCache;
use Amasty\CheckoutCore\Model\OrderAttribute\UpdateSortOrder;
use Magento\Eav\Model\Attribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as AttributeResource;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class OrderAttributes implements ProcessorInterface
{
    /**
     * @var ModuleEnable
     */
    private $moduleEnable;

    /**
     * @var GetOrderAttributes
     */
    private $getOrderAttributes;

    /**
     * @var SetAttributeFrontendLabel
     */
    private $setAttributeFrontendLabel;

    /**
     * @var AttributeResource
     */
    private $attributeResource;

    public function __construct(
        ModuleEnable $moduleEnable,
        GetOrderAttributes $getOrderAttributes,
        SetAttributeFrontendLabel $setAttributeFrontendLabel,
        AttributeResource $attributeResource
    ) {
        $this->moduleEnable = $moduleEnable;
        $this->getOrderAttributes = $getOrderAttributes;
        $this->setAttributeFrontendLabel = $setAttributeFrontendLabel;
        $this->attributeResource = $attributeResource;
    }

    public function process(array $fields, int $storeId): array
    {
        if (!$this->moduleEnable->isOrderAttributesEnable() || empty($fields)) {
            return $fields;
        }

        $attributes = $this->getOrderAttributes->execute($storeId);
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

            $attribute->setData(InvalidateCache::FLAG_NO_INVALIDATE, true);
            $attribute->setData(UpdateSortOrder::FLAG_NO_UPDATE, true);
            $this->attributeResource->save($attribute);
            unset($fields[$attributeId]);
        }

        return $fields;
    }

    private function updateAttribute(Attribute $attribute, array $fieldData): void
    {
        $isEnabled = (bool) $fieldData[Field::ENABLED];
        $isRequired = $isEnabled && isset($fieldData['required']);
        $attribute->setData('sorting_order', $fieldData['sort_order']);
        $attribute->setData('is_visible_on_front', $isEnabled);
        $attribute->setData('is_visible_on_back', $isEnabled);
        $attribute->setData('required_on_front_only', false);
        $attribute->setIsRequired($isRequired);
    }
}
