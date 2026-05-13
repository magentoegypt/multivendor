<?php

namespace Vnecoms\VendorsCredit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsCredit\Model\CreditProcessor\RefundSpentCredit;

class BackCredit implements ObserverInterface
{
    /**
     * @var \Vnecoms\Credit\Model\Processor
     */
    protected $creditProcessor;

    /**
     * @var \Vnecoms\Credit\Model\CreditFactory
     */
    protected $creditAccountFactory;

    /**
     * @var \Vnecoms\Credit\Model\Credit\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * OrderCancelAfter constructor.
     * @param \Vnecoms\Credit\Model\Processor $creditProcessor
     * @param \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory
     * @param \Vnecoms\Credit\Model\Credit\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Vnecoms\Credit\Model\Processor $creditProcessor,
        \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory,
        \Vnecoms\Credit\Model\Credit\TransactionFactory $transactionFactory
    ) {
        $this->creditProcessor      = $creditProcessor;
        $this->creditAccountFactory = $creditAccountFactory;
        $this->transactionFactory   = $transactionFactory;
    }

    public function execute(Observer $observer)
    {
        $vendorOrder = $observer->getOrder();
        $order = $vendorOrder->getOrder();
        $customerId = $order->getCustomerId();
        if(!$customerId) return $this;

        /*Return if the transaction for the invoice is already exist.*/
        $trans = $this->transactionFactory->create()->getCollection()
            ->addFieldToFilter('type',RefundSpentCredit::TYPE)
            ->addFieldToFilter('additional_info',array('like'=>'vendor_order|'.$vendorOrder->getId()));
        if($trans->count() || abs($order->getBaseCreditRefunded()) >= abs($order->getCreditAmount())) return;

        $creditAccount = $this->creditAccountFactory->create();
        $creditAccount->loadByCustomerId($customerId);

        $baseTotalCreditAmount = 0;
        $totalCreditAmount = 0;
        foreach($vendorOrder->getAllItems() as $orderItem){
            if ($orderItem->isDummy()) {
                continue;
            }
            $baseTotalCreditAmount += (double)$orderItem->getBaseCreditAmount();
            $totalCreditAmount += (double)$orderItem->getCreditAmount();
        }


        $returnAmount = abs($baseTotalCreditAmount);
        if($returnAmount == 0) return;
        $data = array(
            'type'		=> RefundSpentCredit::TYPE,
            'amount'	=> $returnAmount,
            'order'		=> $order,
            'vendor_order' => $vendorOrder
        );

        $this->creditProcessor->process($creditAccount,$data);
        $order->setCreditRefunded($order->getCreditRefunded() + $totalCreditAmount);
        $order->setBaseCreditRefunded($order->getBaseCreditRefunded() + $baseTotalCreditAmount);
        $order->save();
    }
}
