<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vnecoms\Quotation\Block\Customer;

class Pager extends \Magento\Theme\Block\Html\Pager
{

    protected $_template = 'Vnecoms_Quotation::customer/pager.phtml';

    protected function _construct()
    {
        parent::_construct();
        $this->setAvailableLimit([
            10  => 10,
            15  => 15,
            50  => 50,
            100 => 100
        ]);
    }
}
