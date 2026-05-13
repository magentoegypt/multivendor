<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\Page;

class Index extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::page';

    /**
     * Index action.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        // $a = $this->_objectManager->get('Vnecoms\VendorsCms\Model\Template\FilterProvider');

       // var_dump($a->getFilterNotes());die();
        $this->_initAction();
        $this->setActiveMenu('Vnecoms_VendorsCms::page');
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $this->_addBreadcrumb(__('CMS'), __('CMS'));
        $this->_addBreadcrumb(__('Manage Pages'), __('Manage Pages'));
        $title->prepend(__('Pages'));

        $dataPersistor = $this->_objectManager->get('Magento\Framework\App\Request\DataPersistorInterface');
        $dataPersistor->clear('vendor_cms_page');

        $this->_view->renderLayout();
    }
}
