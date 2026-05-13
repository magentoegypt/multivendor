<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\App;

class Edit extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app';
    /**
     * Edit widget instance action.
     */
    public function execute()
    {
        $app = $this->_initApp();
        if (!$app) {
            $this->_redirect('*/*/');

            return;
        }

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $app->getId() ? $app->getTitle() : __('New Frontend Application')
        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Frontend Applications'));
        $this->_view->renderLayout();
    }
}
