<?php

namespace Vnecoms\VendorsProductImportExport\Controller\Vendors\Export;

class Index extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::product_import_media';
    
    /**
     * @return void
     */
    public function execute()
    {
        $this->getRequest()->setParam('vendor_id', $this->_session->getVendor()->getId());
        $this->_initAction();
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Catalog"));
        $title->prepend(__("Export Products"));
        $this->setActiveMenu('Vnecoms_Vendors::product_export');
        
        $this->_addBreadcrumb(__("Catalog"), __("Catalog"))->_addBreadcrumb(__("Export Products"), __("Export Products"));
        $this->_view->renderLayout();
    }
}
