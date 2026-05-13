<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCredit\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsCredit\Model\CreditProcessor\OrderPayment;
use Vnecoms\VendorsCredit\Model\CreditProcessor\RefundOrderPayment;
use Vnecoms\VendorsCredit\Model\CreditProcessor\RefundItemCommission;

class ProcessRefund implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;

    /**
     * @var \Vnecoms\Credit\Model\Processor
     */
    protected $_creditProcessor;

    /**
     * @var \Vnecoms\Credit\Model\Credit\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Vnecoms\Credit\Model\CreditFactory
     */
    protected $_creditAccountFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Vnecoms\VendorsCredit\Model\EscrowFactory
     */
    protected $_escrowFactory;

    /**
     * @var \Vnecoms\VendorsSales\Model\OrderFactory
     */
    protected $vendorOrderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Invoice\ItemFactory
     */
    protected $invoiceItemFactory;

    /**
     * @var \Vnecoms\VendorsCredit\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * ProcessRefund constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Vnecoms\Credit\Model\Processor $creditProcessor
     * @param \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory
     * @param \Vnecoms\Credit\Model\Credit\TransactionFactory $transactionFactory
     * @param \Vnecoms\VendorsCredit\Model\EscrowFactory $escrowFactory
     * @param \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory
     * @param \Magento\Sales\Model\Order\Invoice\ItemFactory $invoiceItemFactory
     * @param \Vnecoms\VendorsCredit\Helper\Data $helper
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Vnecoms\Credit\Model\Processor $creditProcessor,
        \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory,
        \Vnecoms\Credit\Model\Credit\TransactionFactory $transactionFactory,
        \Vnecoms\VendorsCredit\Model\EscrowFactory $escrowFactory,
        \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory,
        \Magento\Sales\Model\Order\Invoice\ItemFactory $invoiceItemFactory,
        \Vnecoms\VendorsCredit\Helper\Data $helper,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_vendorFactory = $vendorFactory;
        $this->_transactionFactory = $transactionFactory;
        $this->_escrowFactory = $escrowFactory;
        $this->_creditAccountFactory = $creditAccountFactory;
        $this->_creditProcessor = $creditProcessor;
        $this->_productFactory = $productFactory;
        $this->vendorOrderFactory = $vendorOrderFactory;
        $this->invoiceItemFactory = $invoiceItemFactory;
        $this->helper = $helper;
        $this->_eventManager = $eventManager;
    }

    /**
     * Add multiple vendor order row for each vendor.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isEnableCredit()) {
            return;
        }
        $creditmemo = $observer->getCreditmemo();
        $vendorOrderId = $creditmemo->getData('vendor_order_id');
        $vendorOrder = $this->vendorOrderFactory->create()->load($vendorOrderId);
        /*Do nothing if the creditmemo is not related to a vendor*/
        if(!$vendorOrder->getId()) return;

        /*Add credit to vendor's credit account and calculate commission*/
        $vendor = $this->_vendorFactory->create();
        $vendor->load($vendorOrder->getVendorId());

        /*Do nothing if the vendor is not exist*/
        if (!$vendor->getId()) {
            return;
        }

        $creditAccount = $this->_creditAccountFactory->create();
        $creditAccount->loadByCustomerId($vendor->getCustomer()->getId());
        $this->refundItemCommission($creditAccount, $creditmemo, $vendorOrder);
        $this->refundOrderAmount($creditAccount, $creditmemo, $vendorOrder);
        return $this;
    }

    /**
     * @param $creditAccount
     * @param $creditmemo
     * @param $vendorOrder
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function refundItemCommission($creditAccount, $creditmemo, $vendorOrder){
        $vendorId = $vendorOrder->getVendorId();
        $order = $vendorOrder->getOrder();

        foreach($creditmemo->getAllItems() as $item){
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem  = $item->getOrderItem();
            if ($orderItem->getParentItemId()) {
                continue;
            }

            $trans = $this->_transactionFactory->create()->getCollection()
                ->addFieldToFilter('type', RefundItemCommission::TYPE)
                ->addFieldToFilter('additional_info', ['like'=>'creditmemo_item|'.$item->getId()]);
            if ($trans->count()) {
                return;
            }

            $invoiceItemCollection = $this->invoiceItemFactory->create()->getCollection()
                ->addFieldToFilter('order_item_id', $orderItem->getId());

            $commission = 0;
            $invoicedItemCount = 0;
            foreach($invoiceItemCollection as $invoiceItem){
                $commission += $invoiceItem->getCommission();
                $invoicedItemCount += $invoiceItem->getQty();
            }

            $refundAmount = $commission * $item->getQty() / $invoicedItemCount;
            $data = [
                'vendor'        => $vendorId,
                'type'          => RefundItemCommission::TYPE,
                'amount'        => $refundAmount,
                'creditmemo'    => $creditmemo,
                'order'         => $order,
                'creditmemo_item'   => $item,
                'is_process' => true
            ];


            /*Return if the transaction is exist.*/
            $transport = new \Magento\Framework\DataObject($data);
            $this->_eventManager->dispatch(
                'ves_vendorscredit_refund_commission_credit_before',
                ['transport' => $transport]
            );
            $data = $transport->getData();

            if ($data['is_process'] == true) {
                $this->_creditProcessor->process($creditAccount, $data);
            }
        }

        $vendorOrder->setCreditRefunded($vendorOrder->getCreditRefunded() + $creditmemo->getCreditRefunded());
        $vendorOrder->setBaseCreditRefunded($vendorOrder->getBaseCreditRefunded() + $creditmemo->getBaseCreditRefunded());
        $vendorOrder->save();
    }
    /**
     * Refund vendor order amount
     *
     * @param $creditAccount
     * @param $creditmemo
     * @param $vendorOrder
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function refundOrderAmount($creditAccount, $creditmemo, $vendorOrder){

        /*Return if the transaction is exist.*/
        $trans = $this->_transactionFactory->create()->getCollection()
            ->addFieldToFilter('type', RefundOrderPayment::TYPE)
            ->addFieldToFilter('additional_info', ['like'=>'creditmemo|'.$creditmemo->getId()]);
        if ($trans->count()) {
            return;
        }


        $data = [
            'vendor'        => $vendorOrder->getVendorId(),
            'type'          => RefundOrderPayment::TYPE,
            'amount'        => $creditmemo->getBaseGrandTotal() + abs($creditmemo->getBaseCreditAmount()),
            'vendor_order'  => $vendorOrder,
            'creditmemo'    => $creditmemo,
            'order'         => $vendorOrder->getOrder(),
            'is_process' => true
        ];

        /*Return if the transaction is exist.*/
        $transport = new \Magento\Framework\DataObject($data);
        $this->_eventManager->dispatch(
            'ves_vendorscredit_refund_credit_before',
            ['transport' => $transport]
        );
        $data = $transport->getData();

        if ($data['is_process'] == true) {
            $this->_creditProcessor->process($creditAccount, $data);
        }

    }
}
