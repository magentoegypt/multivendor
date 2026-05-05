<?php

namespace Mstore\Wishlist\Api;

/**
 * Interface WishlistManagementInterface
 * @package Mstore\Wishlist\Api
 * @api
 */
interface WishlistManagementInterface
{
    /**
     * Add wishlist item to shopping cart and remove from wishlist
     *
     * If Product has required options - item removed from wishlist and redirect
     * to product view page with message about needed defined required options
     *
     * @param int $customerId
     * @param int $itemId
     * @param int|null $qty
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Catalog\Model\Product\Exception
     * @throws \Exception
     */
    public function addCart($customerId, $itemId, $qty = null): bool;

    /**
     * @param int $customerId
     * @param string[]|null $qtys
     * @return \Mstore\Wishlist\Api\Data\MessageBagInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Catalog\Model\Product\Exception
     */
    public function allCart($customerId, $qtys = null);
}
