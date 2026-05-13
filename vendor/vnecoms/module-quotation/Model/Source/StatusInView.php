<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
namespace Vnecoms\Quotation\Model\Source;

use Magento\Framework\Option\ArrayInterface;
use Vnecoms\Quotation\Model\Quote;

/**
 * Status Options in View Page(just remove starting option)
 * Class StatusInView
 * @package Vnecoms\Quotation\Model\Source
 */
class StatusInView extends Status implements ArrayInterface
{
    /**
     * Possible types
     * Remove STATUS_CREATED status
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();

        foreach ($options as $id => $option) {
            if ($option['value'] = Quote::STATUS_CREATED) unset($options[$id]); break;
        }

        return $options;
    }
}
