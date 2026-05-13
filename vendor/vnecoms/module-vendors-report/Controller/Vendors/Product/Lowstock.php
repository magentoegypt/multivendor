<?php

namespace Vnecoms\VendorsReport\Controller\Vendors\Product;

class Lowstock extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsReport::report_products_low_stock';
    /**
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $this->setActiveMenu('Vnecoms_VendorsReport::report_products_low_stock');
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Reports"));
        $title->prepend(__("Low Stock Report"));
        $this->_addBreadcrumb(__("Reports"), __("Reports"))->_addBreadcrumb(__("Low Stock Report"), __("Low Stock Report"));
        $this->_view->renderLayout();
    }
}
