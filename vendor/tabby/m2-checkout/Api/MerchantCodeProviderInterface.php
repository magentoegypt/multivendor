<?php

namespace Tabby\Checkout\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface for merchant code provider
 * @api
 * @since 7.0.0
 */
interface MerchantCodeProviderInterface
{
    /**
     * Get merchant code for product
     *
     * @param ProductInterface $product
     * @return string
     */
    public function getMerchantCodeForProduct(ProductInterface $product) : string;
    /**
     * Get merchant code for cart
     *
     * @param CartInterface $quote
     * @return string
     */
    public function getMerchantCodeForCart(CartInterface $quote) : string;
    /**
     * Get merchant code for Order
     *
     * @param OrderInterface $order
     * @return string
     */
    public function getMerchantCodeForOrder(OrderInterface $order) : string;
}
