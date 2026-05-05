<?php

namespace Mstore\Wishlist\Api\Data;

interface WishlistInterface
{
    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @return string
     */
    public function getSharingCode();

    /**
     * Get the amount of items in the wishlist
     *
     * @return int
     */
    public function getItemsCount();

    /**
     * @return \Mstore\Wishlist\Api\Data\ItemInterface[]
     */
    public function getItems();
}
