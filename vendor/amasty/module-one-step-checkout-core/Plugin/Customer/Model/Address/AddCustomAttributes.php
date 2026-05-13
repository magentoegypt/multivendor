<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Customer\Model\Address;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Amasty\CheckoutCore\Model\Customer\Address\IgnoreValidationFlag;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Address;

class AddCustomAttributes
{
    /**
     * @var IgnoreValidationFlag
     */
    private $ignoreValidationFlag;

    public function __construct(
        IgnoreValidationFlag $ignoreValidationFlag
    ) {
        $this->ignoreValidationFlag = $ignoreValidationFlag;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateData(Address $subject, Address $result, AddressInterface $address): Address
    {
        foreach (CustomFieldsConfigInterface::CUSTOM_FIELDS_ARRAY as $attribute) {
            // Customer Address don't have public getData method
            $attributeValue = $address->getCustomAttribute($attribute);
            if ($attributeValue !== null) {
                $result->setData($attribute, $attributeValue->getValue());
            }
        }

        if ($this->ignoreValidationFlag->shouldIgnore()) {
            $result->setData('should_ignore_validation', true);
        }

        return $result;
    }
}
