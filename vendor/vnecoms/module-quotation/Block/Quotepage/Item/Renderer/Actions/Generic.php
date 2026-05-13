<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Quotepage\Item\Renderer\Actions;

use Magento\Framework\View\Element\Template;
use Vnecoms\Quotation\Model\Item;

class Generic extends Template
{
    /**
     * @var Item
     */
    protected $item;

    /**
     * Returns current quote item
     *
     * @return Item
     * @codeCoverageIgnore
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set current quote item
     *
     * @param Item $item
     * @return $this
     * @codeCoverageIgnore
     */
    public function setItem(Item $item)
    {
        $this->item = $item;
        return $this;
    }

    /**
     * Check if product is visible in site visibility
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isProductVisibleInSiteVisibility()
    {
        return $this->getItem()->getProduct()->isVisibleInSiteVisibility();
    }
}
