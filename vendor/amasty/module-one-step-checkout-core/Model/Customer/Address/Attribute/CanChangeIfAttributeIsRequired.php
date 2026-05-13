<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Customer\Address\Attribute;

class CanChangeIfAttributeIsRequired
{
    /**
     * @var GetRestrictedCodes
     */
    private $getRestrictedCodes;

    public function __construct(GetRestrictedCodes $getRestrictedCodes)
    {
        $this->getRestrictedCodes = $getRestrictedCodes;
    }

    /**
     * Decides if one can change if the attribute is required or not.
     *
     * @param string $attributeCode
     * @return bool
     */
    public function execute(string $attributeCode): bool
    {
        return !in_array($attributeCode, $this->getRestrictedCodes->execute());
    }
}
