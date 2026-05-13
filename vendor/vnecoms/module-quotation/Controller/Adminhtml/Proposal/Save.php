<?php


namespace Vnecoms\Quotation\Controller\Adminhtml\Proposal;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

class Save extends \Magento\Backend\App\Action implements HttpPostActionInterface
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $id = $this->getRequest()->getParam('proposal_id');
        $model = $this->_objectManager->create('Vnecoms\Quotation\Model\Proposal')->load($id);
        $data = [
            'item_id' => $this->getRequest()->getParam('item_id'),
            'price' => $this->getRequest()->getParam('price'),
            'base_price' => $this->getRequest()->getParam('price'),
            'qty' => $this->getRequest()->getParam('qty'),
        ];
        $model->addData($data);

        try {
            $model->save();
            $response->setData([
                'error' => false,
                'proposal_id' => $model->getId()
            ]);
        } catch (LocalizedException $e) {
            $response->setData([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $response->setData([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->_resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
