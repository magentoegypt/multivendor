<?php

namespace Vnecoms\Credit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Vnecoms\Credit\Model\Processor\RefundSpentCredit;

class OrderCancelAfter implements ObserverInterface
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
        $order = $observer->getOrder();
        $customerId = $order->getCustomerId();
        if (!$customerId) {
            return $this;
        }

        /*Return if the transaction for the invoice is already exist.*/
        $trans = $this->transactionFactory->create()->getCollection()
            ->addFieldToFilter('type', RefundSpentCredit::TYPE)
            ->addFieldToFilter('additional_info', ['like'=>'order|'.$order->getId()]);
        if ($trans->count()) {
            return;
        }

        try {
            $creditAccount = $this->creditAccountFactory->create();
            $creditAccount->loadByCustomerId($customerId);

            $returnAmount = abs($order->getBaseCreditAmount());
            if ($returnAmount == 0) {
                return;
            }
            $data = [
                'type'        => RefundSpentCredit::TYPE,
                'amount'    => $returnAmount,
                'order'        => $order,
            ];

            $this->creditProcessor->process($creditAccount, $data);
        } catch (\Exception $e) {
            //to do something
        }
    }
}
