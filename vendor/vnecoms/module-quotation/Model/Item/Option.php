<?php

namespace Vnecoms\Quotation\Model\Item;

use \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;

class Option extends \Magento\Framework\DataObject implements OptionInterface
{
    /**
     * Get option value
     * @see \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface::getValue()
     */
    public function getValue(){
        return $this->getData('value');
    }
}