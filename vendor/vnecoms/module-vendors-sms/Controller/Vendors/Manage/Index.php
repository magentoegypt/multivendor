<?php
namespace Vnecoms\VendorsSms\Controller\Vendors\Manage;

class Index extends \Vnecoms\Vendors\Controller\Vendors\Action
{

    /**
     * @return void
     */
    public function execute()
    {
        $this->getRequest()->setParam('vendor_id', $this->_session->getVendor()->getId());
        $this->_initAction();
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Manage SMS"));
        
        $this->_addBreadcrumb(__("Manage SMS"), __("Manage SMS"));
        $this->_view->renderLayout();
    }
}
