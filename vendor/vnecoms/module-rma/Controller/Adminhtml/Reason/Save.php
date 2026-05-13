<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/21/2016
 * Time: 05:01 PM
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Reason;

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
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var \Vnecoms\RMA\Model\Reason
     */
    protected $_model;

    /**
     * Save constructor.
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     * @param \Vnecoms\RMA\Model\Reason $reason
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        \Vnecoms\RMA\Model\Reason $reason,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->_model         = $reason;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
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
            /** @var \Vnecoms\RMA\Model\Reason $model */
            $model = $this->_model;

            $id = $this->getRequest()->getParam('reason_id');
            if (empty($data['reason_id'])) {
                $data['reason_id'] = null;
            }
            if ($id) {
                $model->load($id);
            }
            $model->setData($data);
            $this->_eventManager->dispatch(
                'rma_reason_prepare_save',
                ['reason' => $model, 'request' => $this->getRequest()]
            );

            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved this reason.'));
                $this->dataPersistor->clear('rma_reason');
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['reason_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }

            $this->dataPersistor->set('rma_reason', $data);
            return $resultRedirect->setPath('*/*/edit', ['reason_id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
