<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\App;

class Save extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app_action_save';
    /**
     * Save action.
     */
    public function execute()
    {
        $app = $this->_initApp();
        if (!$app) {
            $this->_redirect('cms/*/');

            return;
        }
        //echo "<pre>";var_dump($this->getRequest()->getParams());die();
        $app->setTitle(
            $this->getRequest()->getPost('title')
        )->setSortOrder(
            $this->getRequest()->getPost('sort_order', 0)
        )->setPageGroups(
            $this->getRequest()->getPost('app_instance')
        )->setParameters(
            $this->getRequest()->getPost('parameters')
        )->setVendorId(
            $this->getVendor()->getId()
        );
        $this->_eventManager->dispatch('vendor_cms_app_prepare_save', ['app' => $app]);
        try {
            $app->save();
            $this->messageManager->addSuccess(__('The application has been saved.'));
            if ($this->getRequest()->getParam('back', false)) {
                $this->_redirect(
                    'cms/*/edit',
                    ['app_id' => $app->getId(), '_current' => true]
                );
            } else {
                $this->_redirect('cms/*/');
            }

            return;
        } catch (\Exception $exception) {
            $this->messageManager->addError($exception->getMessage());
            $this->_logger->critical($exception);
            $this->_redirect('cms/*/edit', ['_current' => true]);

            return;
        }
        $this->_redirect('cms/*/');

        return;
    }
}
