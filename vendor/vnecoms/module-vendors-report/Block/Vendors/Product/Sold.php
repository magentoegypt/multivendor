<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsReport\Block\Vendors\Product;

/**
 * Backend Report Sold Product Content Block
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Sold extends \Vnecoms\Vendors\Block\Vendors\Widget\Grid\Container
{
    /**
     * @var string
     */
    protected $_blockGroup = 'Vnecoms_VendorsReport';

    /**
     * Initialize container block settings
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Vnecoms_VendorsReport';
        $this->_controller = 'vendors_product_sold';
        $this->_headerText = __('Products Ordered');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
