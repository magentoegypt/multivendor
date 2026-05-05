<?php

namespace Mstore\Wishlist\Api\Data;

use Magento\Catalog\Api\Data\ProductInterface;

interface ItemInterface
{
    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return int
     */
    public function getQty(): int;

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProduct(): ProductInterface;
}
