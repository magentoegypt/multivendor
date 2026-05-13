<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form;

use Amasty\CheckoutCore\Model\ResourceModel\AttributeFormCodes;
use Magento\Eav\Model\Entity\Attribute;

class SelectFormCodes
{
    /**
     * @param Attribute $attribute
     * @param array $fieldData
     * @return string[]
     */
    public function execute(Attribute $attribute, array $fieldData): array
    {
        $formCodes = [
            AttributeFormCodes::ADMINHTML_CUSTOMER,
            AttributeFormCodes::AMASTY_CUSTOM_ATTRIBUTES
        ];

        if ($attribute->getIsVisibleOnFront()) {
            $formCodes[] = AttributeFormCodes::CUSTOMER_ACCOUNT_EDIT;
        }

        if ($attribute->getData('on_registration')) {
            $formCodes[] = AttributeFormCodes::CUSTOMER_ACCOUNT_CREATE;
            $formCodes[] = AttributeFormCodes::AMASTY_CUSTOM_ATTRIBUTES_REGISTRATION;
        }

        if ($fieldData['enabled']) {
            $formCodes[] = AttributeFormCodes::ADMINHTML_CHECKOUT;
            $formCodes[] = AttributeFormCodes::AMASTY_CUSTOM_ATTRIBUTES_CHECKOUT;
        }

        return $formCodes;
    }
}
