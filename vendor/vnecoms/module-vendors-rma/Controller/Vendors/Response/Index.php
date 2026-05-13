<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:28 AM
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Response;

class Index extends \Vnecoms\VendorsRMA\Controller\Vendors\Vendors
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_response';
    /**
     * @return void
     */
    public function execute()
    {
        $this->getRequest()->setParam('vendor_id',$this->_session->getVendor()->getId());
        $this->_initAction()->_addBreadcrumb(__('RMA'), __('Response'));
        $this->setActiveMenu('Vnecoms_Vendors::rma_response');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('RMA - Response'));
        $this->_view->renderLayout();
    }
}