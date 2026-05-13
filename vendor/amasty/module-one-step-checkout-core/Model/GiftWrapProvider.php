<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Amasty\CheckoutCore\Api\GiftWrapProviderInterface;

/**
 * Provide data to GraphQl submodule
 */
class GiftWrapProvider implements GiftWrapProviderInterface
{
    /**
     * @return bool
     */
    public function isGiftWrapEnabled(): bool
    {
        return false;
    }

    /**
     * @return float
     */
    public function getGiftWrapFee(): float
    {
        return 0.0;
    }
}
