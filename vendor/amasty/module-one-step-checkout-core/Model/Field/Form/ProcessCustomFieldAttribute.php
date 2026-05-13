<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\IsCustomFieldAttribute;
use Amasty\CheckoutCore\Model\Field\SetAttributeFrontendLabel;
use Amasty\CheckoutCore\Model\ResourceModel\GetCustomerAddressAttributeById;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ProcessCustomFieldAttribute
{
    /**
     * @var IsCustomFieldAttribute
     */
    private $isCustomFieldAttribute;

    /**
     * @var GetCustomerAddressAttributeById
     */
    private $getCustomerAddressAttributeById;

    /**
     * @var SetAttributeFrontendLabel
     */
    private $setAttributeFrontendLabel;

    /**
     * @var AttributeResource
     */
    private $attributeResource;

    public function __construct(
        IsCustomFieldAttribute $isCustomFieldAttribute,
        GetCustomerAddressAttributeById $getCustomerAddressAttributeById,
        SetAttributeFrontendLabel $setAttributeFrontendLabel,
        AttributeResource $attributeResource
    ) {
        $this->isCustomFieldAttribute = $isCustomFieldAttribute;
        $this->getCustomerAddressAttributeById = $getCustomerAddressAttributeById;
        $this->setAttributeFrontendLabel = $setAttributeFrontendLabel;
        $this->attributeResource = $attributeResource;
    }

    /**
     * @param Field $field
     * @return void
     * @throws AlreadyExistsException
     */
    public function execute(Field $field): void
    {
        $attributeId = $field->getAttributeId();
        if ($field->isEnabled() && $this->isCustomFieldAttribute->execute($attributeId)) {
            $attribute = $this->getCustomerAddressAttributeById->execute($attributeId);

            if ($attribute) {
                $this->setAttributeFrontendLabel->execute(
                    $attribute,
                    $field->getStoreId(),
                    $field->getData('label')
                );

                $this->attributeResource->save($attribute);
            }
        }
    }
}
