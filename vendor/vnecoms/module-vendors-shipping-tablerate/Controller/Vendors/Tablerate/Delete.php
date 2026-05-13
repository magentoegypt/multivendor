<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/22/2016
 * Time: 11:18 AM
 */
namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate;

class Delete extends \Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate
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
        $id = $this->getRequest()->getParam('rate_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create('Vnecoms\VendorsShippingTableRate\Model\Tablerate');
                $model->load($id);

                if (!$model->getId() || !$this->rateAuthorization->canView($model)) {
                    $this->messageManager->addError(__('This Rate no longer exists.'));
                    return $resultRedirect->setPath("*/*/");
                }

                $model->delete();
                // display success message
                $this->messageManager->addSuccess(__('You deleted the rate.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['rate_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__('We can\'t find a rate to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}