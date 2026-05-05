<?php

namespace Mstore\Wishlist\Api\Data;

interface BuyRequestInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    /*
     * Supper attribute
     */
    const SUPER_ATTRIBUTE = 'super_attribute';
    /*
     * Qty
     */
    const QTY = 'qty';

    /**
     * @return string[]
     */
    public function getSuperAttribute();

    /**
     * @return int
     */
    public function getQty();

    /**
     * @param string|string[] $attribute
     * @return $this
     */
    public function setSuperAttribute($attribute);

    /**
     * @param int $qty
     * @return $this
     */
    public function setQty($qty);
}
