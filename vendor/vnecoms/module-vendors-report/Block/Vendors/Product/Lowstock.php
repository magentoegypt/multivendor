<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsReport\Block\Vendors\Product;

class Lowstock extends \Vnecoms\Vendors\Block\Vendors\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Vnecoms_VendorsReport';
        $this->_controller = 'vendors_product_lowstock';
        $this->_headerText = __('Low stock');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
