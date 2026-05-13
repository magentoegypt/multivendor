<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Setup\Operation;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes\UpdateAttributeFromField;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\UpdateConfig;
use Amasty\CheckoutCore\Model\ResourceModel\Field\CollectionFactory as FieldCollectionFactory;
use Amasty\CheckoutCore\Model\ResourceModel\GetCustomerAddressAttributeById;

class SyncWithCheckoutFields
{
    /**
     * @var FieldCollectionFactory
     */
    private $fieldCollectionFactory;

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

    public function __construct(
        FieldCollectionFactory $fieldCollectionFactory,
        GetCustomerAddressAttributeById $getCustomerAddressAttributeById,
        UpdateConfig $updateConfig,
        UpdateAttributeFromField $updateAttributeFromField
    ) {
        $this->fieldCollectionFactory = $fieldCollectionFactory;
        $this->getCustomerAddressAttributeById = $getCustomerAddressAttributeById;
        $this->updateConfig = $updateConfig;
        $this->updateAttributeFromField = $updateAttributeFromField;
    }

    public function execute(): void
    {
        $collection = $this->fieldCollectionFactory->create()
            ->addFieldToFilter(Field::STORE_ID, Field::DEFAULT_STORE_ID);

        /** @var Field $field */
        foreach ($collection->getItems() as $field) {
            $this->updateConfig->execute($field);

            $attribute = $this->getCustomerAddressAttributeById->execute($field->getAttributeId());
            if ($attribute) {
                $this->updateAttributeFromField->execute($field, $attribute);
            }
        }
    }
}
