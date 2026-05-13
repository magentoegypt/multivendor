<?php

namespace Vnecoms\VendorsSellerList\Plugin\Controller;

class SellerList
{
    /** @var \Magento\Framework\Json\Helper\Data  */
	protected $_jsonHelper;

	/** @var \Vnecoms\VendorsSellerList\Helper\Data  */
	protected $_sellerListHelper;

	public function __construct(
		\Magento\Framework\Json\Helper\Data $jsonHelper,
		\Vnecoms\VendorsSellerList\Helper\Data $sellerListHelper
	){
		$this->_jsonHelper = $jsonHelper;
		$this->_sellerListHelper = $sellerListHelper;
	}

    public function afterExecute(
        \Vnecoms\VendorsSellerList\Controller\Index\Index $action,
        $page
    ){
		if($action->getRequest()->getParam('isAjax')){
			$sellerListBlock = $page->getLayout()->getBlock('seller_list');
			$sellers = $sellerListBlock->setTemplate('Vnecoms_VendorsSellerList::sellerlist-ajax.phtml');
			$result = [
			    'sellers' => $sellers->toHtml(),
			    'page'=> $action->getRequest()->getParam('p'),
			];
			$action->getResponse()->representJson($this->_jsonHelper->jsonEncode($result));
		} else {
			return $page;
		}
    }
}
