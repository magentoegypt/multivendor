<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Status;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Vnecoms\RMA\Model\ResourceModel\Status\CollectionFactory;
use Vnecoms\RMA\Model\Status;

/**
 * Class MassDisable
 */
class MassStatus extends \Magento\Backend\App\Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Status $status
    ) {
        $this->filter = $filter;
        $this->_model = $status;
        $this->collectionFactory = $collectionFactory->create();
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory);
        $collectionSize = $collection->getSize();
        $statusIds = $collection->getAllIds();
        $status = (int) $this->getRequest()->getParam('status');
        $model = $this->_model;
        try {
            foreach ($statusIds as $id) {
                $model->load($id);
                $model->setStatus($status);
                $model->save();
            }
            $this->messageManager->addSuccess(__('A total of %1 record(s) have been updated.', $collectionSize));
//            $this->_productPriceIndexerProcessor->reindexList($productIds);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->_getSession()->addException($e, __('Something went wrong while updating the status(s) status.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*');
    }
}
