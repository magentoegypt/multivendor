<?php

namespace Vnecoms\VendorsCoupon\Controller\Vendors\Index;


class Index extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCoupon::coupon';
    
    /**
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Manage Coupons"));
        $this->setActiveMenu('Vnecoms_VendorsCoupon::coupon');
        $this->_addBreadcrumb(__("Manage Coupons"), __("Manage Coupons"));
        $this->_view->renderLayout();
    }
}
