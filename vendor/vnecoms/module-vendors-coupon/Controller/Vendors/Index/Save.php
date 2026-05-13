<?php

namespace Vnecoms\VendorsCoupon\Controller\Vendors\Index;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCoupon::coupon_save';

    public function execute()
    {
        $id = $this->getRequest()->getParam('coupon_id');
        $coupon = $this->_objectManager->create('Vnecoms\VendorsCoupon\Model\Coupon');
        $coupon->load($id);

        if(!$coupon->getId() || $coupon->getVendorId() != $this->_session->getVendor()->getId()){
            $this->messageManager->addError(__("The coupon is not available !"));
            return $this->_redirect('coupon');
        }

        try{
            $coupon->setData('action', $this->getRequest()->getParam('action'));
            $coupon->setData('buy_x', $this->getRequest()->getParam('buy_x'));
            $coupon->setData('amount', $this->getRequest()->getParam('amount'));
            $coupon->setData('usage_limit', $this->getRequest()->getParam('usage_limit'));
            $coupon->setData('from_date', $this->getRequest()->getParam('from_date'));
            $coupon->setData('to_date', $this->getRequest()->getParam('to_date'));
            $coupon->save();
            $this->messageManager->addSuccess(__('The coupon is saved!'));
        }catch (\Exception $e){
            $this->messageManager->addError($e->getMessage());
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('coupon');
    }
}
