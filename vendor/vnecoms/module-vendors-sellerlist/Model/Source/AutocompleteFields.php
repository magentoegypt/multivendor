<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vnecoms\VendorsSellerList\Model\Source;

class AutocompleteFields
{
    const SUGGEST = 'suggest';

    const SELLER = 'seller';

    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->options = [
           /* ['value' => self::SUGGEST, 'label' => __('Suggested')],*/
            ['value' => self::SELLER, 'label' => __('Sellers')],
        ];
   
        return $this->options;
    }
}
