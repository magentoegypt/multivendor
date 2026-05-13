<?php
namespace Vnecoms\VendorsShipping\ViewModel\Shipment\Create;

class Form implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $_carrierFactory;

    /**
     * Form constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     * @param \Magento\Sales\Helper\Admin $admin
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Sales\Helper\Admin $admin
    ) {
        $this->_coreRegistry = $registry;
        $this->_carrierFactory = $carrierFactory;
    }
    /**
     * @return \Vnecoms\Vendors\Model\Order
     */
    public function getVendorOrder()
    {
        $vendorOrder =  $this->_coreRegistry->registry('vendor_order');
        if (!$vendorOrder || !$vendorOrder->getId()) {
            $vendorOrder =  $this->getOrder();
        }
        return $vendorOrder;
    }

    /**
     * Get price data object
     *
     * @return Order|mixed
     */
    public function getPriceDataObject()
    {
        return $this->getVendorOrder();
    }

    /**
     * Check is carrier has functionality of creation shipping labels
     *
     * @return bool
     */
    public function canCreateShippingLabel()
    {
        $shippingCarrier = $this->_carrierFactory->create(
            $this->getVendorOrder()->getShippingMethod(true)->getCarrierCode()
        );
        return $shippingCarrier && $shippingCarrier->isShippingLabelsAvailable();
    }
}
