<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\ViewModel\Frontend\Customer\Address\Edit;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Amasty\CheckoutCore\Model\CustomField\AddressStorage;
use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\ResourceModel\Field as ResourceField;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class CustomFields implements ArgumentInterface
{
    /**
     * @var CollectionFactory
     */
    private $eavCollectionFactory;

    /**
     * @var AddressStorage
     */
    private $addressStorage;

    public function __construct(
        AddressStorage $addressStorage,
        CollectionFactory $eavCollectionFactory
    ) {
        $this->addressStorage = $addressStorage;
        $this->eavCollectionFactory = $eavCollectionFactory;
    }
    public function getAttributes(): array
    {
        $eavCollection =  $this->eavCollectionFactory->create();
        $eavCollection->joinLeft(
            ResourceField::MAIN_TABLE,
            'main_table.attribute_id = ' . ResourceField::MAIN_TABLE . '.' . Field::ATTRIBUTE_ID,
            null
        );

        $eavCollection->addFieldToFilter(ResourceField::MAIN_TABLE . '.' . Field::ENABLED, 1);
        $eavCollection->addFieldToFilter(
            [
                AttributeMetadataInterface::ATTRIBUTE_CODE,
                AttributeMetadataInterface::ATTRIBUTE_CODE,
                AttributeMetadataInterface::ATTRIBUTE_CODE
            ],
            [
                ['eq' => CustomFieldsConfigInterface::CUSTOM_FIELD_1_CODE],
                ['eq' => CustomFieldsConfigInterface::CUSTOM_FIELD_2_CODE],
                ['eq' => CustomFieldsConfigInterface::CUSTOM_FIELD_3_CODE]
            ]
        );

        $eavCollection->addAttributeGrouping();

        return $eavCollection->getItems();
    }

    public function getValue(string $attributeCode): string
    {
        $address = $this->addressStorage->getAddress();

        if ($address && $address->getCustomAttribute($attributeCode)) {
            return $address->getCustomAttribute($attributeCode)->getValue();
        }

        return '';
    }
}
