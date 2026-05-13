<?php

namespace Vnecoms\VendorsCoupon\Controller\Vendors\Index;

use Magento\Framework\Controller\ResultFactory;

class Delete extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCoupon::coupon_delete';
    
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $coupon = $this->_objectManager->create('Vnecoms\VendorsCoupon\Model\Coupon');
        $coupon->load($id);
        
        if(!$coupon->getId() || $coupon->getVendorId() != $this->_session->getVendor()->getId()){
            $this->messageManager->addError(__("The coupon is not available !"));
            return $this->_redirect('coupon');
        }
        
        try{
            $couponCode = $coupon->getCode();
            $coupon->delete();
            $this->messageManager->addSuccess(__('The coupon %1 is deleted successfully!',$couponCode));
        }catch (\Exception $e){
            $this->messageManager->addError($e->getMessage());
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('coupon');
    }
}
