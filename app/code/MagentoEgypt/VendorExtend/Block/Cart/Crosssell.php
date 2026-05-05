<?php 
namespace MagentoEgypt\VendorExtend\Block\Cart;

class Crosssell extends \Magento\Checkout\Block\Cart\Crosssell
{
	protected $_maxItemCount = 8;
	
	protected function _getCollection()
	{
		$collection = parent::_getCollection();
		$collection->getSelect()->orderRand('e.entity_id');
		return $collection;
	}
}