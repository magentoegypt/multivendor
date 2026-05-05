<?php

namespace Mstore\Wishlist\Model;

interface WishlistProviderInterface
{
    /**
     * Retrieve wishlist
     *
     * @param int $wishlistId
     * @param int $customerId
     * @return \Magento\Wishlist\Model\Wishlist
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWishlist($wishlistId, $customerId);
}
