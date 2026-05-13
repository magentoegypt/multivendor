<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Used in creating options for Yes|No config value selection.
 */

namespace Vnecoms\VendorsCategory\Model\Source;

class Type implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'app/vertical.phtml', 'label' => __('Vertical Menu')],
            ['value' => 'app/horizontal.phtml', 'label' => __('Horizontal Menu')]
        ];
    }

    /**
     * Get options in "key-value" format.
     *
     * @return array
     */
    public function toArray()
    {
        return ['app/vertical.phtml' => __('Vertical Menu'), 'app/horizontal.phtml' => __('Horizontal Menu')];
    }
}
