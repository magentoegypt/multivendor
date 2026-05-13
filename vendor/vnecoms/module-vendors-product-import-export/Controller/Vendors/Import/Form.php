<?php

namespace Vnecoms\VendorsProductImportExport\Controller\Vendors\Import;

class Form extends \Vnecoms\Vendors\Controller\Vendors\Action
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
        $title->prepend(__("Import Form"));
        $this->setActiveMenu('Vnecoms_Vendors::product_import');
        $this->_addBreadcrumb(__("Catalog"), __("Catalog"))->_addBreadcrumb(__("Import"), __("Import"), $this->getUrl('catalog/import'))->_addBreadcrumb(__("Import Form"), __("Import Form"));
        $this->_view->renderLayout();
    }
}
