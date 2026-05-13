<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\VendorsSellerList\Model\SellerList;

/**
 * Class Toolbar
 * @package Vnecoms\VendorsSellerList\Plugin\Model\Product\ProductList
 */
class Toolbar extends \Magento\Catalog\Model\Product\ProductList\Toolbar
{
    /**
     * SEller per page limit order cookie name
     */
    const SELLER_LIMIT_PARAM_NAME = 'seller_list_limit';

    /**
     * Get products per page limit
     *
     * @return string|bool
     */
    public function getLimit()
    {
        //echo 1;die;
        return $this->request->getParam(self::SELLER_LIMIT_PARAM_NAME);
    }
}