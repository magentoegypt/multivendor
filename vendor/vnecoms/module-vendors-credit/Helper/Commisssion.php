<?php

namespace Vnecoms\VendorsCredit\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Vnecoms\VendorsCredit\Model\CreditProcessor\OrderPayment;
use Vnecoms\VendorsCredit\Model\CreditProcessor\ItemCommission;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Commisssion extends AbstractHelper
{
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
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Vnecoms\VendorsCredit\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * Commisssion constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Vnecoms\Credit\Model\Processor $creditProcessor
     * @param \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory
     * @param \Vnecoms\Credit\Model\Credit\TransactionFactory $transactionFactory
     * @param \Vnecoms\VendorsCredit\Model\EscrowFactory $escrowFactory
     * @param Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Vnecoms\Credit\Model\Processor $creditProcessor,
        \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory,
        \Vnecoms\Credit\Model\Credit\TransactionFactory $transactionFactory,
        \Vnecoms\VendorsCredit\Model\EscrowFactory $escrowFactory,
        \Vnecoms\VendorsCredit\Helper\Data $helper,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->_vendorFactory = $vendorFactory;
        $this->_transactionFactory = $transactionFactory;
        $this->_escrowFactory = $escrowFactory;
        $this->_creditAccountFactory = $creditAccountFactory;
        $this->_creditProcessor = $creditProcessor;
        $this->_productFactory = $productFactory;
        $this->helper = $helper;
        $this->_eventManager = $eventManager;
    }


    /**
     * @param $groupId
     * @return bool
     */
    public function isEnabledEscrowFeature($groupId)
    {
        return $this->helper->isEnabledEscrowTransaction($groupId);
    }

    /**
     * @param $vendorInvoice
     * @param $ignoreEscrow
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processCommission(
        $vendorInvoice,
        $ignoreEscrow
    ) {
        if (!$this->helper->isEnableCredit()) {
            return;
        }

        /*Don't calculate commission if the invoice has not been paid*/
        if ($vendorInvoice->getState() != \Vnecoms\VendorsSales\Model\Order\Invoice::STATE_PAID) {
            return;
        }

        $vendorOrder = $vendorInvoice->getOrder();
        /* Add credit to vendor account */
        if (!$vendorOrder->getVendorId()) {
            return;
        }

        $vendor = $this->_vendorFactory->create();
        $vendor->load($vendorOrder->getVendorId());

        if (!$vendor->getId()) {
            return;
        }

        if ($this->isEnabledEscrowFeature($vendor->getGroupId()) && !$ignoreEscrow) {
            return $this->createEscrowTransaction($vendorInvoice);
        }

        $order = $vendorOrder->getOrder();
        /*Return if the transaction is exist.*/
        $trans = $this->_transactionFactory->create()->getCollection()
            ->addFieldToFilter('type', OrderPayment::TYPE)
            ->addFieldToFilter('additional_info', ['like'=>'vendor_invoice|'.$vendorInvoice->getId()]);
        if ($trans->count()) {
            return;
        }

        $creditAccount = $this->_creditAccountFactory->create();
        $creditAccount->loadByCustomerId($vendor->getCustomer()->getId());

        $amount = $vendorInvoice->getBaseGrandTotal();

        if ($vendorInvoice->getBaseCreditAmount() > 0) {
            $amount += $vendorInvoice->getBaseCreditAmount();
        }

        /*Create transaction to add invoice grandtotal to vendor credit account.*/
        $data = [
            'vendor' => $vendor,
            'type' => OrderPayment::TYPE,
            'amount' => $amount,
            'vendor_order' => $vendorOrder,
            'vendor_invoice' => $vendorInvoice,
            'order' => $order
        ];

        $transport = new \Magento\Framework\DataObject($data);
        $this->_eventManager->dispatch(
            'ves_vendorscredit_calculate_credit_before',
            ['transport' => $transport]
        );
        $data = $transport->getData();

        $this->_creditProcessor->process($creditAccount, $data);

        /*Calculate commission and create transaction for each item.*/
        foreach ($vendorInvoice->getAllItems() as $item) {
            $orderItem  = $item->getOrderItem();
            if ($orderItem->getParentItemId()) {
                continue;
            }

            $trans = $this->_transactionFactory->create()->getCollection()
                ->addFieldToFilter('type', ItemCommission::TYPE)
                ->addFieldToFilter('additional_info', ['like'=>'invoice_item|'.$item->getId().'%']);
            if ($trans->count()) {
                continue;
            }
            $fee = $item->getCommission();
            /*Do nothing if the fee is zero*/
            if ($fee <= 0) {
                continue;
            }

            $additionalDescription = $item->getCommissionDescription();

            $data = [
                'vendor' => $vendor,
                'type' => ItemCommission::TYPE,
                'amount' => $fee,
                'invoice_item' => $item,
                'order' => $order,
                'vendor_invoice' => $vendorInvoice,
                'additional_description' => $additionalDescription,
            ];

            $this->_creditProcessor->process($creditAccount, $data);
        }
    }

    /**
     * Create escrow transaction
     * @param \Vnecoms\VendorsSales\Model\Order\Invoice $vendorInvoice
     */
    public function createEscrowTransaction(\Vnecoms\VendorsSales\Model\Order\Invoice $vendorInvoice)
    {
        /*Add credit to vendor's credit account and calculate commission*/
        $vendor = $this->_vendorFactory->create();
        $vendor->load($vendorInvoice->getVendorId());

        /*Do nothing if the vendor is not exist*/
        if (!$vendor->getId()) {
            return;
        }

        $amount         = $vendorInvoice->getBaseGrandTotal();

        if ($vendorInvoice->getBaseCreditAmount() > 0) {
            $amount += $vendorInvoice->getBaseCreditAmount();
        }

        $data = [
            'vendor_id'      => $vendor->getId(),
            'relation_id'    => $vendorInvoice->getId(),
            'vendor_invoice' => $vendorInvoice,
            'amount'         => $amount,
            'status'         => \Vnecoms\VendorsCredit\Model\Escrow::STATUS_PENDING,
            'additional_info'    => 'order|'.$vendorInvoice->getOrder()->getId().'|invoice|'.$vendorInvoice->getId(),
        ];

        /*Return if the transaction is exist.*/
        $transport = new \Magento\Framework\DataObject($data);
        $this->_eventManager->dispatch(
            'ves_vendorscredit_calculate_credit_before',
            ['transport' => $transport]
        );
        $data = $transport->getData();

        $escrows = $this->_escrowFactory->create()->getCollection()
            ->addFieldToFilter('relation_id', $vendorInvoice->getId());
        if ($escrows->count()) {
            $currentEscrow = $escrows->getFirstItem();

            $escrow = $this->_escrowFactory->create()->load($currentEscrow->getId());
            $escrow->setData($data)->setId($currentEscrow->getId())->save();
        } else {
            $escrow = $this->_escrowFactory->create();
            $escrow->setData($data)->save();
        }

        /* Send notification email*/
    }

}
