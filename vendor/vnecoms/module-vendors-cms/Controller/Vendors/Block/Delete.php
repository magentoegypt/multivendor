<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\Block;

class Delete extends \Vnecoms\VendorsCms\Controller\Vendors\Block
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::block_action_delete';
    /**
     * Delete action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('block_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create('Vnecoms\VendorsCms\Model\Block');
                $model->load($id);
                if ($model->getVendorId() !== $this->getVendor()->getId()) {
                    $this->messageManager->addError(__('You can\'t delete this Block.'));
                    return $resultRedirect->setPath('*/*/');
                }
                $model->delete();
                // display success message
                $this->messageManager->addSuccess(__('You deleted the block.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['block_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__('We can\'t find a block to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
