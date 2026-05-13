<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Observer\Admin\Customer\Attribute;

use Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes\UpdateField;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

class UpdateFieldAfterSave implements ObserverInterface
{
    /**
     * @var UpdateField
     */
    private $updateField;

    public function __construct(UpdateField $updateField)
    {
        $this->updateField = $updateField;
    }

    /**
     * Event: customer_entity_attribute_save_after
     *
     * @param Observer $observer
     * @return void
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Customer\Model\Attribute $attribute */
        $attribute = $observer->getData('attribute');
        $entityTypeCode = $attribute->getEntityType()->getEntityTypeCode();
        if ($entityTypeCode !== AddressMetadataInterface::ENTITY_TYPE_ADDRESS) {
            return;
        }

        $this->updateField->execute($attribute);
    }
}
