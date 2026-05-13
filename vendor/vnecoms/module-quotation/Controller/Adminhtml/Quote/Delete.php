<?php


namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Delete extends \Vnecoms\Quotation\Controller\Adminhtml\Quote implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Vnecoms_Quotation::delete';

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
        $id = $this->getRequest()->getParam('quote_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->quoteRepository->getById($id);
                $this->quoteRepository->delete($model);
                // display success message
                $this->messageManager->addSuccessMessage(__('You deleted the Quote.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/view', ['quote_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a Quote to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
