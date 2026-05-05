<?php

namespace Mstore\Checkout\Model;

interface ConfigProviderInterface
{
    /**
     * Retrieve assoc array of checkout configuration
     * @param int $cartId
     * @return string[]
     */
    public function getConfig($cartId = null);
}
