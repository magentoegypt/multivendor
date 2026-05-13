<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Sales;

use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\AttributeInterfaceFactory;
use Magento\Framework\Api\CustomAttributesDataInterface;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class WrapCustomAttributes
{
    /**
     * @var AttributeInterfaceFactory
     */
    private $attributeValueFactory;

    public function __construct(AttributeInterfaceFactory $attributeValueFactory)
    {
        $this->attributeValueFactory = $attributeValueFactory;
    }

    /**
     * CoreCopyFieldsetOrderAddressToCustomerAddress doesn't wrap custom attribute values
     * in AttributeInterface objects and uses regular arrays instead, e.g. [attributeCode => value].
     * This leads to @see \Magento\Framework\Reflection\CustomAttributesProcessor failing as it expects
     * attribute value to be the instance of AttributeInterface.
     *
     * This method wraps values in objects to prevent Magento from failing.
     *
     * @param array $addressData
     * @return array
     * @see \Magento\CustomerCustomAttributes\Observer\CoreCopyFieldsetOrderAddressToCustomerAddress
     */
    public function execute(array $addressData): array
    {
        if (!isset($addressData[CustomAttributesDataInterface::CUSTOM_ATTRIBUTES])) {
            return $addressData;
        }

        $customAttributes = $addressData[CustomAttributesDataInterface::CUSTOM_ATTRIBUTES];

        $result = [];
        foreach ($customAttributes as $attributeCode => $customAttribute) {
            $result[$attributeCode] = !$customAttribute instanceof AttributeInterface ?
                $this->wrapValue($attributeCode, $customAttribute) :
                $customAttribute;
        }

        $addressData[CustomAttributesDataInterface::CUSTOM_ATTRIBUTES] = $result;
        return $addressData;
    }

    /**
     * @param string $attributeCode
     * @param mixed $value
     * @return AttributeInterface
     */
    private function wrapValue(string $attributeCode, $value): AttributeInterface
    {
        return $this->attributeValueFactory->create()
            ->setAttributeCode($attributeCode)
            ->setValue($value);
    }
}
