<?php

namespace Vnecoms\VendorsShipping\Plugin;

use Magento\Framework\Registry;

class ShipmentLoader
{
    /**
     * @var \Vnecoms\VendorsSales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Vnecoms\VendorsShipping\Helper\Data
     */
    protected $helper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * ShipmentLoader constructor.
     * @param Registry $registry
     * @param \Vnecoms\VendorsSales\Model\OrderFactory $orderFactory
     * @param \Vnecoms\VendorsShipping\Helper\Data $helper
     */
    public function __construct(
        Registry $registry,
        \Vnecoms\VendorsSales\Model\OrderFactory $orderFactory,
        \Vnecoms\VendorsShipping\Helper\Data $helper
    ) {
        $this->registry = $registry;
        $this->orderFactory = $orderFactory;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $subject
     * @param $result
     * @return mixed
     */
    public function afterLoad(
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $subject,
        $result
    ) {
        if(!$this->helper->isEnabled()) return $result;
        $vendorOrder = $this->registry->registry('vendor_order');
        if (!$vendorOrder) {
            $vendorOrderId = $result->getVendorOrderId();
            $vendorOrder = $this->orderFactory->create()->load($vendorOrderId);
            $this->registry->register('vendor_order', $vendorOrder);
        }
        return $result;
    }
}
