<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Item\Price;

use Vnecoms\Quotation\Model\Item;

/**
 * Item price render block
 *
 * @author Vnecoms Core Team <core@vnecoms.com>
 */
class Renderer extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Item
     */
    protected $item;

    /**
     * Set item for render
     *
     * @param Item $item
     * @return $this
     */
    public function setItem(Item $item)
    {
        $this->item = $item;
        return $this;
    }

    /**
     * Get quote item
     *
     * @return Item
     */
    public function getItem()
    {
        return $this->item;
    }
}
