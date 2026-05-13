<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Customer\Model\Metadata\AddressMetadata;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Model\Metadata\AddressMetadata;

class AddCustomAttributes
{
    /**
     * @param AddressMetadata $subject
     * @param array $result
     * @param string $dataObjectClassName
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetCustomAttributesMetadata(
        AddressMetadata $subject,
        array $result,
        $dataObjectClassName = AddressMetadataInterface::DATA_INTERFACE_NAME
    ): array {
        foreach ($subject->getAllAttributesMetadata() as $attribute) {
            if (in_array($attribute->getAttributeCode(), CustomFieldsConfigInterface::CUSTOM_FIELDS_ARRAY, true)) {
                $result[] = $attribute;
            }
        }

        return $result;
    }
}
