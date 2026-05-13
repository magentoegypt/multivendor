<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\Backend\App\Action
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Vnecoms_Quotation::save';


    protected $dataPersistor;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
    )
    {
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
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = $this->getRequest()->getParam('quote_id');

            $model = $this->_objectManager->create('Vnecoms\Quotation\Model\Quote')->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addErrorMessage(__('This Quote no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            $data = $this->postProcessData($data);
            $model->setData($data['quote']);

            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the Quote.'));
                $this->dataPersistor->clear('vnecoms_quotation_quote');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['quote_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Quote.'));
            }

            $this->dataPersistor->set('vnecoms_quotation_quote', $data);
            return $resultRedirect->setPath('*/*/edit', ['quote_id' => $this->getRequest()->getParam('quote_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    protected function postProcessData(array $data)
    {
        return $data;
    }
}
