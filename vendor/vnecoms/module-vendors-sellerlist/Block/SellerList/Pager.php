<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\VendorsSellerList\Block\SellerList;

/**
 * Class with class map capability
 *
 * ...
 */
class Pager extends \Magento\Theme\Block\Html\Pager
{

    /**
     * @var string
     */
    protected $_limitVarName = 'seller_list_limit';

    /**
     * The list of available pager limits
     *
     * @var array
     */
    protected $_availableLimit = [5 => 5,10 => 10, 20 => 20, 40 => 40, 80 => 80, 100 => 100];

}