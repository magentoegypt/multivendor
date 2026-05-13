<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api;

interface DeliveryDateStatisticInterface
{
    /**
     * @param array $quoteIds
     * @param int $quoteTotalCount
     * @return array
     */
    public function collect(array $quoteIds = [], int $quoteTotalCount = 1): array;
}
