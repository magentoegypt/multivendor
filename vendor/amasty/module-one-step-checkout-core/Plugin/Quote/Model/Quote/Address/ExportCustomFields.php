<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Quote\Model\Quote\Address;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Address;

class ExportCustomFields
{
    /**
     * @param Address $subject
     * @param AddressInterface $result
     * @return AddressInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExportCustomerAddress(Address $subject, AddressInterface $result): AddressInterface
    {
        foreach (CustomFieldsConfigInterface::CUSTOM_FIELDS_ARRAY as $attribute) {
            // Customer Address don't have public getData method, but have setData.
            // Custom attributes should be set using setCustomAttribute
            $result->setCustomAttribute($attribute, $subject->getData($attribute));
        }

        return $result;
    }
}
