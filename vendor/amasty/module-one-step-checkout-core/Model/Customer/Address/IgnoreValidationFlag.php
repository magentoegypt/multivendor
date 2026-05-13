<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Customer\Address;

class IgnoreValidationFlag
{
    /**
     * @var bool
     */
    private $shouldIgnore = false;

    public function shouldIgnore(): bool
    {
        return $this->shouldIgnore;
    }

    public function setShouldIgnore(bool $shouldIgnore): void
    {
        $this->shouldIgnore = $shouldIgnore;
    }
}
