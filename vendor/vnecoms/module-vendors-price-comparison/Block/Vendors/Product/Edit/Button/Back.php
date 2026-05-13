<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Block\Vendors\Product\Edit\Button;

use Vnecoms\VendorsProduct\Block\Vendors\Product\Edit\Button\Generic;

/**
 * Class Back
 */
class Back extends Generic
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/')),
            'class' => ' fa  fa-angle-left back',
            'sort_order' => 10
        ];
    }
}
