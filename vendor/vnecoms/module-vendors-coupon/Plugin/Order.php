<?php
namespace Vnecoms\VendorsCoupon\Plugin;

class Order
{
    /**
     * @var \Vnecoms\VendorsSales\Model\OrderFactory
     */
    protected $_vendorOrderFactory;

    /**
     * Order constructor.
     * @param \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory
     */
    public function __construct(
        \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory
    ) {
        $this->_vendorOrderFactory = $vendorOrderFactory;
    }

    /**
     * Check if code exists in vendor coupon table
     *
     * @param string $code
     * @return bool
     */

    public function aroundGetDiscountDescription(
        \Magento\Sales\Model\Order $subject,
        \Closure $proceed
    ) {
        $result = $proceed();

        $vendorOrders = $this->_vendorOrderFactory->create()->getCollection()->addFieldToFilter("order_id", $subject->getId());
        foreach ($vendorOrders as $vendorOrder) {
            $result .= ", ".$vendorOrder->getData('discount_description');
        }
        if (!$result) return null;
        $result = trim($result);
        $result = trim($result, ",");
        $result = trim($result);
        return $result;
    }
}
