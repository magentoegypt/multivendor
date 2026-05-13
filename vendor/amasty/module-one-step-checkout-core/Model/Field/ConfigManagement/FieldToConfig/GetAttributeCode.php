<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\ResourceModel\GetCustomerAddressAttributeById;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class GetAttributeCode
{
    /**
     * @var GetCustomerAddressAttributeById
     */
    private $getCustomerAddressAttributeById;

    public function __construct(GetCustomerAddressAttributeById $getCustomerAddressAttributeById)
    {
        $this->getCustomerAddressAttributeById = $getCustomerAddressAttributeById;
    }

    public function execute(Field $field): ?string
    {
        $attributeId = $field->getAttributeId();
        if (!$attributeId) {
            return null;
        }

        $attribute = $this->getCustomerAddressAttributeById->execute($attributeId);
        if (!$attribute) {
            return null;
        }

        return $attribute->getAttributeCode();
    }
}
