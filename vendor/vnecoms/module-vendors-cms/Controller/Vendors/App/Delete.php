<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\App;

class Delete extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app_action_delete';
    /**
     * Delete Action.
     */
    public function execute()
    {
        $app = $this->_initApp();
        if ($app->getVendorId() !== $this->getVendor()->getId()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addError(__('You can\'t delete this App.'));
            return $resultRedirect->setPath('*/*/');
        }
        if ($app) {
            try {
                $app->delete();
                $this->messageManager->addSuccess(__('The CMS Application has been deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');

        return;
    }
}
