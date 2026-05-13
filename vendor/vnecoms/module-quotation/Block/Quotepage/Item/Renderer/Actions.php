<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Quotepage\Item\Renderer;

use Vnecoms\Quotation\Block\Quotepage\Item\Renderer\Actions\Generic;
use Magento\Framework\View\Element\Text;
use Vnecoms\Quotation\Model\Item;

class Actions extends Text
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
     * Render html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->setText('');

        $layout = $this->getLayout();
        foreach ($this->getChildNames() as $child) {
            /** @var Generic $childBlock */
            $childBlock = $layout->getBlock($child);
            if ($childBlock instanceof Generic) {
                $childBlock->setItem($this->getItem());
                $this->addText($layout->renderElement($child, false));
            }
        }

        return parent::_toHtml();
    }
}
