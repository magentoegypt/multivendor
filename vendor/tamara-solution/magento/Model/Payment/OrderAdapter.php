<?php
namespace Tamara\Checkout\Model\Payment;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;

class OrderAdapter implements OrderAdapterInterface
{
    private $originalOrder;

    public function __construct($order)
    {
        $this->originalOrder = $order;
    }

    public function getId()
    {
        return $this->originalOrder->getEntityId();
    }

    public function getOrderIncrementId()
    {
        return $this->originalOrder->getIncrementId();
    }

    public function getCustomerId()
    {
        return $this->originalOrder->getCustomerId();
    }

    public function getBillingAddress()
    {
        return new AddressAdapter($this->originalOrder->getBillingAddress());
    }

    public function getShippingAddress()
    {
        if (!$this->originalOrder->getShippingAddress()) {
            return null;
        }
        return new AddressAdapter($this->originalOrder->getShippingAddress());
    }

    public function getCurrencyCode()
    {
        return $this->originalOrder->getOrderCurrencyCode();
    }

    public function getOrderCurrencyCode()
    {
        return $this->originalOrder->getOrderCurrencyCode();
    }

    public function getBaseCurrencyCode()
    {
        return $this->originalOrder->getBaseCurrencyCode();
    }

    public function getGlobalCurrencyCode()
    {
        return $this->originalOrder->getGlobalCurrencyCode();
    }

    public function getStoreId()
    {
        return $this->originalOrder->getStoreId();
    }

    public function getItems()
    {
        return $this->originalOrder->getItems();
    }
    
    public function getGrandTotalAmount()
    {
        return $this->originalOrder->getGrandTotalAmount();
    }

    public function getRemoteIp()
    {
        return $this->originalOrder->getRemoteIp();
    }
}
