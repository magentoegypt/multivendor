<?php

namespace Vnecoms\VendorsCoupon\Controller\Vendors\Index;

class Edit extends \Vnecoms\Vendors\Controller\Vendors\Action
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
        $coupon = $this->_objectManager->create('Vnecoms\VendorsCoupon\Model\Coupon');
        $coupon->load($this->getRequest()->getParam('id'));
        
        if(!$coupon->getId() || $coupon->getVendorId() != $this->_session->getVendor()->getId()){
            $this->messageManager->addError(__("The coupon is not available !"));
            return $this->_redirect('coupon');
        }
        
        $this->_coreRegistry->register('current_coupon', $coupon);
        $this->_coreRegistry->register('coupon', $coupon);
        $this->getRequest()->setParam('coupon_code',$coupon->getCode());
        
        $this->_initAction();
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Manage Coupons"));
        $title->prepend($coupon->getCode());
        $this->_addBreadcrumb(__("Manage Coupons"), __("Manage Coupons"))
            ->_addBreadcrumb($coupon->getCode(), $coupon->getCode());
        $this->_view->renderLayout();

    }
}
