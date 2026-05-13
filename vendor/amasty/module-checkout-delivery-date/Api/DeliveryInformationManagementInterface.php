<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Api;

interface DeliveryInformationManagementInterface
{
    /**
     * @param int $cartId
     * @param string $date
     * @param int|null $time
     * @param string|null $comment
     * @return bool
     */
    public function update($cartId, $date, $time = -1, $comment = ''): bool;
}
