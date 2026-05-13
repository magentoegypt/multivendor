<?php
namespace Vnecoms\VendorsSms\Controller\Vendors\Logs;

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
        $title->prepend(__("SMS Logs"));
        
        $this->_addBreadcrumb(__("SMS Logs"), __("SMS Logs"));
        $this->_view->renderLayout();
    }
}
