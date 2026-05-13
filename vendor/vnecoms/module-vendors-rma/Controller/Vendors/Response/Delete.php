<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/22/2016
 * Time: 11:18 AM
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Response;
class Delete extends \Vnecoms\VendorsRMA\Controller\Vendors\Vendors
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_response';
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
        $id = $this->getRequest()->getParam('reponse_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create('Vnecoms\RMA\Model\Reponse');
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccess(__('You deleted the response.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['reponse_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__('We can\'t find a response to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}