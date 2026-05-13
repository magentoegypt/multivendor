<?php

namespace Vnecoms\VendorsProductImportExport\Controller\Vendors\Import;

class StartQueue extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::product_import';
    
    /**
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Catalog"));
        $title->prepend(__("Import"));
        $title->prepend(__("Start Import Queue"));
        $this->setActiveMenu('Vnecoms_Vendors::product_import');
        $this->_addBreadcrumb(__("Catalog"), __("Catalog"))->_addBreadcrumb(__("Import"), __("Import"), $this->getUrl('catalog/import'))->_addBreadcrumb(__("Start Import Queue"), __("Start Import Queue"));
        $this->_view->renderLayout();
    }
}
