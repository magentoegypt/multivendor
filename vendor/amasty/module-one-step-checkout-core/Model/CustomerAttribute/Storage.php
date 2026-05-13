<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\CustomerAttribute;

class Storage
{
    /**
     * @var string[]
     */
    private $customerAttributesCodes = [];

    /**
     * @return string[]
     */
    public function getCustomerAttributesCodes(): array
    {
        return $this->customerAttributesCodes;
    }

    public function addCustomerAttributeCode(string $code): void
    {
        $this->customerAttributesCodes[] = $code;
    }
}
