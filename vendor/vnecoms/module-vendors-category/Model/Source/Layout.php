<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Used in creating options for Yes|No config value selection.
 */

namespace Vnecoms\VendorsCategory\Model\Source;

class Layout implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => '2columns_left', 'label' => __('2 Columns with Left')], ['value' => '1column', 'label' => __('1 Column')]];
    }

    /**
     * Get options in "key-value" format.
     *
     * @return array
     */
    public function toArray()
    {
        return ['2columns_left' => __('2 Columns with Left'), '1column' => __('1 Column')];
    }
}
