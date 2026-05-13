<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/22/2016
 * Time: 11:18 AM
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

class Delete extends Request
{
    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('request_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create('Vnecoms\RMA\Model\Request');
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccess(__('You deleted the request.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['request_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__('We can\'t find a request to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
