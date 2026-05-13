<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\App;

class Index extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app';
    /**
     * Widget Instances Grid.
     */
    public function execute()
    {
        $this->_initAction();
        $this->setActiveMenu('Vnecoms_VendorsCms::app');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('CMS Frontend Applications'));
        $this->_view->renderLayout();
    }
}
