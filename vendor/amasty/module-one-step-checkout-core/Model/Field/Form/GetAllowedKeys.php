<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form;

use Amasty\CheckoutCore\Model\Customer\Address\Attribute\CanChangeIfAttributeIsRequired;
use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\ResourceModel\GetCustomerAddressAttributeById;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class GetAllowedKeys
{
    /**
     * @var CanChangeIfAttributeIsRequired
     */
    private $canChangeIfAttributeIsRequired;

    /**
     * @var GetCustomerAddressAttributeById
     */
    private $getCustomerAddressAttributeById;

    /**
     * @var array<string, string>
     */
    private $allowedKeys;

    /**
     * @param CanChangeIfAttributeIsRequired $canChangeIfAttributeIsRequired
     * @param GetCustomerAddressAttributeById $getCustomerAddressAttributeById
     * @param array<string, string> $allowedKeys
     */
    public function __construct(
        CanChangeIfAttributeIsRequired $canChangeIfAttributeIsRequired,
        GetCustomerAddressAttributeById $getCustomerAddressAttributeById,
        array $allowedKeys = []
    ) {
        $this->canChangeIfAttributeIsRequired = $canChangeIfAttributeIsRequired;
        $this->getCustomerAddressAttributeById = $getCustomerAddressAttributeById;
        $this->allowedKeys = $allowedKeys;
    }

    /**
     * @param array $fieldData
     * @return string[]
     */
    public function execute(array $fieldData): array
    {
        $result = $this->allowedKeys;

        if (empty($fieldData[Field::ENABLED])) {
            unset($result[Field::SORT_ORDER]);
        }

        $attribute = $this->getCustomerAddressAttributeById->execute($fieldData[Field::ATTRIBUTE_ID]);
        if (!$this->canChangeIfAttributeIsRequired->execute($attribute->getAttributeCode())) {
            unset($result[Field::REQUIRED]);
        }

        return array_values($result);
    }
}
