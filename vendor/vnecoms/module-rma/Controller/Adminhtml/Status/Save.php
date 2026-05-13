<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/21/2016
 * Time: 05:01 PM
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Status;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Magento\Framework\App\Request\DataPersistorInterface;

class Save extends Action
{

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;
    /**
     * @var \Vnecoms\RMA\Model\Status
     */
    protected $_model;
    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * Save constructor.
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     * @param \Vnecoms\RMA\Model\Status $status
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        \Vnecoms\RMA\Model\Status $status,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->_model         = $status;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Vnecoms_RMA::save');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            /** @var \Vnecoms\RMA\Model\Status $model */
            $model = $this->_model;

            $id = $this->getRequest()->getParam('status_id');
            if (empty($data['status_id'])) {
                $data['status_id'] = null;
            }
            if ($id) {
                $model->load($id);
            }
            $model->setData($data);
            $this->_eventManager->dispatch(
                'rma_status_prepare_save',
                ['status' => $model, 'request' => $this->getRequest()]
            );

            try {
                $model->save();
                
                $this->messageManager->addSuccess(__('You saved this status.'));
                $this->dataPersistor->clear('rma_status');
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['status_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }

            $this->dataPersistor->set('rma_status', $data);
            return $resultRedirect->setPath('*/*/edit', ['status_id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
