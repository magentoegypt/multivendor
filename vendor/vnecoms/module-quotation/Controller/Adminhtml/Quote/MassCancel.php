<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Vnecoms\Quotation\Model\Quote;
use Vnecoms\Quotation\Model\ResourceModel\Quote\CollectionFactory;

class MassCancel extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(Context $context, Filter $filter, CollectionFactory $collectionFactory)
    {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Cancel selected orders
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countCancelOrder = 0;
        foreach ($collection->getItems() as $quote) {
            /**
             * @var Quote $quote
             */
            if (!$quote->canCancel()) {
                continue;
            }
            $quote->cancel();
            $quote->save();
            $countCancelOrder++;
        }
        $countNonCancelOrder = $collection->count() - $countCancelOrder;

        if ($countNonCancelOrder && $countCancelOrder) {
            $this->messageManager->addError(__('%1 quote(s) cannot be canceled.', $countNonCancelOrder));
        } elseif ($countNonCancelOrder) {
            $this->messageManager->addError(__('You cannot cancel the quote(s).'));
        }

        if ($countCancelOrder) {
            $this->messageManager->addSuccess(__('We canceled %1 quote(s).', $countCancelOrder));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
