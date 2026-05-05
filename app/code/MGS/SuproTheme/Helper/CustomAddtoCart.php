<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MGS\SuproTheme\Helper;

/**
 * Contact base helper
 */
class CustomAddtoCart extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $_listProduct;

	public function __construct(
		\Magento\Catalog\Block\Product\ListProduct $listPorduct
	)
	{
		$this->_listProduct = $listPorduct;

	}

	public function getAddToCartPostParams($product){
		return $this->_listProduct->getAddToCartPostParams($product);
	}
}