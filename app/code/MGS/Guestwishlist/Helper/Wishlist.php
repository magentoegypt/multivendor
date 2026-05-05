<?php

namespace MGS\Guestwishlist\Helper;

class Wishlist extends \Magento\Wishlist\Helper\Data {
	/**
     * Retrieve customer login status
     *
     * @return bool
     */
    protected function _isCustomerLogIn()
    {
        return $this->_customerSession->isLoggedIn();
    }
	
	/**
     * Retrieve customer wishlist url
     *
     * @param int $wishlistId
     * @return string
     */
    public function getListUrl($wishlistId = null)
    {
        $params = [];
        if ($wishlistId) {
            $params['wishlist_id'] = $wishlistId;
        }
		if($this->_isCustomerLogIn()){
			return $this->_getUrl('wishlist', $params);
		}else{
			return $this->_getUrl('guestwishlist');
		}
        
    }
}
