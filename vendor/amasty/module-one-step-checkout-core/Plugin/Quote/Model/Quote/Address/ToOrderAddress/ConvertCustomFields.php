<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Quote\Model\Quote\Address\ToOrderAddress;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Address\ToOrderAddress;
use Magento\Sales\Api\Data\OrderAddressInterface;

class ConvertCustomFields
{
    /**
     * @param ToOrderAddress $subject
     * @param OrderAddressInterface $result
     * @param AddressInterface $object
     * @param array $data
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterConvert(
        ToOrderAddress $subject,
        OrderAddressInterface $result,
        AddressInterface $quoteAddress,
        $data = []
    ): OrderAddressInterface {
        foreach (CustomFieldsConfigInterface::CUSTOM_FIELDS_ARRAY as $attribute) {
            $result->setData($attribute, $quoteAddress->getData($attribute));
        }

        return $result;
    }
}
