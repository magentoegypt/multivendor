<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Amasty\CheckoutCore\Api\DeliveryDateStatisticInterface;

class DeliveryDateStatistic implements DeliveryDateStatisticInterface
{
    /**
     * Used for provide Delivery Date data from submodule
     *
     * @param array $quoteIds
     * @param int $quoteTotalCount
     * @return array
     */
    public function collect(array $quoteIds = [], int $quoteTotalCount = 1): array
    {
        return [
            'delivery' => [],
            'delivery_total_count' => 0
        ];
    }
}
