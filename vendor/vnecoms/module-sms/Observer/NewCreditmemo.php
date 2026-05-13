<?php
namespace Vnecoms\Sms\Observer;

use Magento\Framework\Event\ObserverInterface;

class NewCreditmemo implements ObserverInterface
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
        
        $creditmemo = $observer->getCreditmemo();
        $order      = $creditmemo->getOrder();
        $additionalData = 'creditmemo|'.$creditmemo->getId();
        
        /* Check if the SMS is sent already*/
        $collection = $this->smsCollectionFactory->create()
            ->addFieldToFilter('additional_data',['like' => $additionalData]);
        if($collection->count()) return;
        $customer = $this->helper->getCustomerObjectForSendingSms($order);
        /* Send notification message to customer when a new credit memo is created*/
        if(
            $this->helper->canSendNewCreditmemoMessage($order->getStoreId())
        ) {
            $message = $this->helper->getNewCreditmemoMessage($order->getStoreId());

            $this->sendSms($order, $creditmemo, $customer, $additionalData, $message);
        }


        $orderMessagesByPayment = $this->helper->getNewCreditMemoMessagesByPayment($order->getStoreId());
        if ($orderMessagesByPayment) {
            $orderMessagesByPayment = json_decode($orderMessagesByPayment, true);
            /* Send new order message by payment method*/
            $payment = $order->getPayment();
            $method = $payment->getMethod();
            if(is_array($orderMessagesByPayment)){
                if(!$customer) $customer = $this->helper->getCustomerObjectForSendingSms($order);
                foreach($orderMessagesByPayment as $message){
                    if($message['payment_method'] != $method) continue;
                    $this->sendSms($order, $creditmemo, $customer, $additionalData, $message['message']);
                }
            }
        }


        $orderMessagesByShipping = $this->helper->getNewCreditMemoMessagesByShipping($order->getStoreId());
        if ($orderMessagesByShipping) {
            $orderMessagesByShipping = json_decode($orderMessagesByShipping, true);
            /* Send new order message by payment method*/
            $method = $order->getShippingMethod();
            $method = explode("_", $method);
            if(is_array($orderMessagesByShipping)){
                if(!$customer) $customer = $this->helper->getCustomerObjectForSendingSms($order);
                foreach($orderMessagesByShipping as $message){
                    if(!in_array($message['shipping_method'], $method)) continue;
                    $this->sendSms($order, $creditmemo, $customer, $additionalData, $message['message']);
                }
            }
        }

    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $creditmemo
     * @param \Magento\Framework\DataObject $customer
     * @param $additionalData
     * @param string $messageTemplate
     */
    public function sendSms(
        \Magento\Sales\Model\Order $order,
        $creditmemo,
        \Magento\Framework\DataObject $customer,
        $additionalData,
        $messageTemplate = ''
    ) {
        $this->filter->setVariables([
            'order' => $order,
            'creditmemo' => $creditmemo,
            'customer' => $customer,
        ]);
        $message = $this->filter->filter($messageTemplate);
        $this->helper->sendCustomerSms($customer, $message, $additionalData);
    }
}
