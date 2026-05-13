<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;

class IsCustomFieldAttribute
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var int[]|null
     */
    private $cachedAttributeIds;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function execute(?int $attributeId = null): bool
    {
        if (empty($attributeId)) {
            return false;
        }

        if ($this->cachedAttributeIds === null) {
            $this->loadAttributeIds();
        }

        return in_array($attributeId, $this->cachedAttributeIds);
    }

    private function loadAttributeIds(): void
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $field = [];
        $condition = [];
        $countCustomFields = CustomFieldsConfigInterface::COUNT_OF_CUSTOM_FIELDS;

        for ($i = 1; $i <= $countCustomFields; $i++) {
            $constNameCustomField
                = '\Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface::CUSTOM_FIELD_' . $i . '_CODE';

            if (!defined($constNameCustomField)) {
                continue;
            }

            $field[] = AttributeMetadataInterface::ATTRIBUTE_CODE;
            $condition[] = ['eq' => constant($constNameCustomField)];
        }

        $collection->addFieldToFilter($field, $condition);

        $this->cachedAttributeIds = [];

        foreach ($collection->getAllIds() as $attributeId) {
            $this->cachedAttributeIds[] = (int) $attributeId;
        }
    }
}
