<?php

namespace Vnecoms\VendorsReport\Controller\Vendors\Sales;

class Hour extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsReport::report_sales_hour';
    /**
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $this->setActiveMenu('Vnecoms_VendorsReport::report_sales_hour');
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Reports"));
        $title->prepend(__("Sales by Hour"));
        $this->_addBreadcrumb(__("Reports"), __("Reports"))->_addBreadcrumb(__("Sales by Hour"), __("Sales by Hour"));
        $this->_view->renderLayout();
    }
}
