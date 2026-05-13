<?php
namespace Vnecoms\Quotation\Controller\Adminhtml\Proposal;

use Magento\Framework\App\Action\HttpPostActionInterface;

class Remove extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('proposal_id');
        try {
            // init model and delete
            $model = $this->_objectManager->create('Vnecoms\Quotation\Model\Proposal');
            $model->load($id);
            $model->delete();
            $response->setData([
                'error' => false,
            ]);
        } catch (\Exception $e) {
            $response->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
