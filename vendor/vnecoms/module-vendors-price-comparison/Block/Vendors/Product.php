<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsPriceComparison\Block\Vendors;

class Product extends \Magento\Backend\Block\Widget\Container
{

    protected function _prepareLayout()
    {
        $this->buttonList->add(
            'select_and_sell',
            [
                'label' => __("Select and Sell"),
                'onclick' => 'setLocation(\'' . $this->getSelectAndSellUrl() . '\')',
                'class' => 'select-and-sell btn-primary fa fa-plus-circle'
            ]
        );
        return parent::_prepareLayout();
    }
    
    /**
     * @return string
     */
    public function getSelectAndSellUrl()
    {
        return $this->getUrl('pricecomparison/selectandsell');
    }
}
