<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api;

interface GiftWrapProviderInterface
{
    /**
     * @return bool
     */
    public function isGiftWrapEnabled(): bool;

    /**
     * @return float
     */
    public function getGiftWrapFee(): float;
}
