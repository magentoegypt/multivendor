<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Vnecoms\Quotation\Model\ResourceModel\Quote\CollectionFactory;

class MassUnhold extends AbstractMassAction implements HttpPostActionInterface
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
     * Unhold selected orders
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countUnHoldQuote = 0;

        /** @var \Vnecoms\Quotation\Model\Quote $quote */
        foreach ($collection->getItems() as $quote) {
            $quote->load($quote->getId());
            if (!$quote->canUnhold()) {
                continue;
            }
            $quote->unhold();
            $countUnHoldQuote++;
        }

        $countNonUnHoldQuote = $collection->count() - $countUnHoldQuote;

        if ($countNonUnHoldQuote && $countUnHoldQuote) {
            $this->messageManager->addError(
                __('%1 quote(s) were not released from on hold status.', $countNonUnHoldQuote)
            );
        } elseif ($countNonUnHoldQuote) {
            $this->messageManager->addError(__('No quote(s) were released from on hold status.'));
        }

        if ($countUnHoldQuote) {
            $this->messageManager->addSuccess(
                __('%1 quote(s) have been released from on hold status.', $countNonUnHoldQuote)
            );
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
