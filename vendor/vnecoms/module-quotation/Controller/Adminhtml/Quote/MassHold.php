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

/**
 * Class MassHold
 */
class MassHold extends AbstractMassAction implements HttpPostActionInterface
{

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Hold selected orders
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countHoldOrder = 0;
        /** @var \Vnecoms\Quotation\Model\Quote $quote */

        foreach ($collection->getItems() as $quote) {
            if (!$quote->canHold()) {
                continue;
            }
            $quote->hold();
            $countHoldOrder++;
        }
        $countNonHoldOrder = $collection->count() - $countHoldOrder;

        if ($countNonHoldOrder && $countHoldOrder) {
            $this->messageManager->addError(__('%1 quote(s) were not put on hold.', $countNonHoldOrder));
        } elseif ($countNonHoldOrder) {
            $this->messageManager->addError(__('No quote(s) were put on hold.'));
        }

        if ($countHoldOrder) {
            $this->messageManager->addSuccess(__('You have put %1 quote(s) on hold.', $countHoldOrder));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
