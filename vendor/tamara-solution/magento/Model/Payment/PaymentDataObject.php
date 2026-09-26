<?php
namespace Tamara\Checkout\Model\Payment;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class PaymentDataObject implements PaymentDataObjectInterface
{
    private $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function getOrder()
    {
        return new OrderAdapter($this->order);
    }

    public function getPayment()
    {
        return $this->order->getPayment();
    }
}
