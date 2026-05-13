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

class MassDelete extends AbstractMassAction implements HttpPostActionInterface
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
     * delete quotes
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countCancelOrder = 0;
        foreach ($collection->getItems() as $quote) {
            $quote->delete();
            $countCancelOrder++;
        }
        $countNonCancelOrder = $collection->count() - $countCancelOrder;

        if ($countNonCancelOrder && $countCancelOrder) {
            $this->messageManager->addError(__('%1 quote(s) cannot be deleted.', $countNonCancelOrder));
        } elseif ($countNonCancelOrder) {
            $this->messageManager->addError(__('You cannot deleted the quote(s).'));
        }

        if ($countCancelOrder) {
            $this->messageManager->addSuccess(__('We deleted %1 quote(s).', $countCancelOrder));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
