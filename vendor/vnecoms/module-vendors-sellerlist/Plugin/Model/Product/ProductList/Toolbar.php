<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\VendorsSellerList\Plugin\Model\Product\ProductList;

/**
 * Class Toolbar
 * @package Vnecoms\VendorsSellerList\Plugin\Model\Product\ProductList
 */
class Toolbar
{
    protected $_url;

    protected $_htmlPagerBlock;

    protected $_request;

    protected $_sellerListHelper;

    /**
     * SEller per page limit order cookie name
     */
    const SELLER_LIMIT_PARAM_NAME = 'seller_list_limit';

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        \Magento\Framework\App\RequestInterface $request,
        \Vnecoms\VendorsSellerList\Helper\Data $sellerListHelper
    ) {
        $this->_url = $url;
        $this->_htmlPagerBlock = $htmlPagerBlock;
        $this->_request = $request;
        $this->_sellerListHelper = $sellerListHelper;
    }


    /**
     * Get products per page limit
     *
     * @return string|bool
     */
    public function aroundGetLimit()
    {
        //echo 1;die;
        return $this->_request->getParam(self::SELLER_LIMIT_PARAM_NAME);
    }
}