<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api;

/**
 * Cache variator interface.
 * Return cache key/identifier part.
 * @since 3.0.0
 */
interface CacheKeyPartProviderInterface
{
    /**
     * @return string
     */
    public function getKeyPart();
}
