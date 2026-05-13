<?php
namespace Vnecoms\Sms\Observer;

use Magento\Framework\Event\ObserverInterface;

class NewInvoice implements ObserverInterface
{
    /**
     * @var \Vnecoms\Sms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Magento\Email\Model\Template\Filter
     */
    protected $filter;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;
    
    /**
     * @var \Vnecoms\Sms\Model\ResourceModel\Sms\CollectionFactory
     */
    protected $smsCollectionFactory;
    
    /**
     * @param \Vnecoms\Sms\Helper\Data $helper
     * @param \Magento\Email\Model\Template\Filter $filter
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Vnecoms\Sms\Model\ResourceModel\Sms\CollectionFactory $smsCollectionFactory
     */
    public function __construct(
        \Vnecoms\Sms\Helper\Data $helper,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Vnecoms\Sms\Model\ResourceModel\Sms\CollectionFactory $smsCollectionFactory
    ){
        $this->helper = $helper;
        $this->filter = $filter;
        $this->customerFactory = $customerFactory;
        $this->smsCollectionFactory = $smsCollectionFactory;
    }
    
    /**
     * Vendor Save After
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->helper->getCurrentGateway()) return;
        
        $invoice    = $observer->getInvoice();
        $order      = $invoice->getOrder();
        
        $additionalData = 'invoice|'.$invoice->getId();
        
        /* Check if the SMS is sent already*/
        $collection = $this->smsCollectionFactory->create()
            ->addFieldToFilter('additional_data',['like' => $additionalData]);
        if($collection->count()) return;
        $customer = false;
        /* Send notification message to customer when a new invoice is created*/
        if(
            $this->helper->canSendNewInvoiceMessage($order->getStoreId())
        ) {
            $customer = $this->helper->getCustomerObjectForSendingSms($order);

            $message = $this->helper->getNewInvoiceMessage($order->getStoreId());
            $this->sendSms($order, $invoice, $customer, $message, $additionalData);
        }

        $orderMessagesByPayment = $this->helper->getNewInvoiceMessagesByPayment($order->getStoreId());
        if ($orderMessagesByPayment) {
            $orderMessagesByPayment = json_decode($orderMessagesByPayment, true);
            /* Send new order message by payment method*/
            $payment = $order->getPayment();
            $method = $payment->getMethod();
            if(is_array($orderMessagesByPayment)){
                if(!$customer) $customer = $this->helper->getCustomerObjectForSendingSms($order);
                foreach($orderMessagesByPayment as $message){
                    if($message['payment_method'] != $method) continue;
                    $this->sendSms($order, $invoice, $customer, $message['message'], $additionalData);
                }
            }
        }


        $orderMessagesByShipping = $this->helper->getNewInvoiceMessagesByShipping($order->getStoreId());

        if ($orderMessagesByShipping) {
            $orderMessagesByShipping = json_decode($orderMessagesByShipping, true);
            /* Send new order message by payment method*/
            $method = $order->getShippingMethod();
            $method = explode("_", $method);

            if(is_array($orderMessagesByShipping)){
                if(!$customer) $customer = $this->helper->getCustomerObjectForSendingSms($order);
                foreach($orderMessagesByShipping as $message){
                    if(!in_array($message['shipping_method'], $method)) continue;
                    $this->sendSms($order, $invoice, $customer, $message['message'], $additionalData);
                }
            }
        }


    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $invoice
     * @param \Magento\Framework\DataObject $customer
     * @param $messageTemplate
     * @param $additionalData
     */
    public function sendSms(
        \Magento\Sales\Model\Order $order,
        $invoice,
        \Magento\Framework\DataObject $customer,
        $messageTemplate,
        $additionalData
    ) {
        $this->filter->setVariables([
            'order' => $order,
            'invoice' => $invoice,
            'customer' => $customer,
            'invoice_total' => $order->formatPriceTxt($invoice->getGrandTotal()),
            'invoice_total_amount' => $order->getOrderCurrency()->formatTxt(
                $invoice->getGrandTotal(),
                ['display' => \Magento\Framework\Currency::NO_SYMBOL]
            ),
        ]);
        $message = $this->filter->filter($messageTemplate);
        $this->helper->sendCustomerSms($customer, $message, $additionalData);
    }
}
