<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Customer\Address\Attribute;

class GetRestrictedCodes
{
    /**
     * @var string[]
     */
    private $restrictedCodes;

    /**
     * @param string[] $restrictedCodes
     */
    public function __construct(array $restrictedCodes = [])
    {
        $this->restrictedCodes = $restrictedCodes;
    }

    public function execute(): array
    {
        return array_values($this->restrictedCodes);
    }
}
