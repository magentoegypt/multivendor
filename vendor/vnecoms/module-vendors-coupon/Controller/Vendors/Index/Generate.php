<?php

namespace Vnecoms\VendorsCoupon\Controller\Vendors\Index;


class Generate extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCoupon::coupon_save';
    
    /**
     * @return void
     */
    public function execute()
    {
        /* if (!$this->getRequest()->isAjax()) {
            $this->_forward('noroute');
            return;
        } */
        try {
            $data = $this->getRequest()->getParams();
            if (!empty($data['expiration_date'])) {
                $inputFilter = new \Zend_Filter_Input(['expiration_date' => $this->_dateFilter], [], $data);
                $data = $inputFilter->getUnescaped();
            }
            
            /** @var $generator \Magento\SalesRule\Model\Coupon\Massgenerator */
            $generator = $this->_objectManager->get('Vnecoms\VendorsCoupon\Model\Coupon\Massgenerator');
            if (!$generator->validateData($data)) {
                $result['error'] = __('Invalid data provided');
            } else {
                $generator->setData($data);
                $generator->setVendorId($this->_session->getVendor()->getId());
                $generator->generatePool();
                $generated = $generator->getGeneratedCount();
                $this->messageManager->addSuccess(__('%1 coupon(s) have been generated.', $generated));
                $this->_view->getLayout()->initMessages();
                $result['messages'] = $this->_view->getLayout()->getMessagesBlock()->getGroupedHtml();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $result['error'] = $e->getMessage();
        } catch (\Exception $e) {
            $result['error'] = __(
                'Something went wrong while generating coupons. Please review the log and try again.'
            );
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }
}
