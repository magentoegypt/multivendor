<?php
namespace Vnecoms\VendorsShipping\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order as ModelOrder;
use Magento\Sales\Api\Data\OrderInterface;

class ShippingDescription
{

    /*Characters between method and vendor_id*/
    const SEPARATOR = '||';

    /*Characters between methods*/
    const METHOD_SEPARATOR = '|_|';


    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     *
     */
    protected $_vendorModel;

    /**
     * @var \Vnecoms\VendorsShipping\Helper\Data
     */
    protected $helper;

    /**
     * @var \Vnecoms\VendorsSales\Model\OrderFactory
     */
    protected $_vendorOrderFactory;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /**
     * ShippingDescription constructor.
     * @param \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendor
     * @param \Vnecoms\VendorsShipping\Helper\Data $helper
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param array $data
     */
    public function __construct(
        \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory,
        \Vnecoms\Vendors\Model\VendorFactory $vendor,
        \Vnecoms\VendorsShipping\Helper\Data $helper,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        array $data = []
    ) {
        $this->_vendorOrderFactory = $vendorOrderFactory;
        $this->_vendorModel = $vendor;
        $this->_vendorHelper = $vendorHelper;
        $this->helper = $helper;
    }

    /**
     * Check if resource for which access is needed has self permissions defined in webapi config.
     *
     * @param \Magento\Framework\Authorization $subject
     * @param callable $proceed
     * @param string $privilege
     *
     * @return bool true If resource permission is self, to allow
     * customer access without further checks in parent method
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetShippingDescription(
        ModelOrder $subject,
        \Closure $proceed
    ) {
        if(!$this->helper->isEnabled()) return $proceed();
        $description = "";
        $vendorOrders = $this->_vendorOrderFactory->create()->getCollection()->addFieldToFilter("order_id", $subject->getId());
        foreach ($vendorOrders as $vendorOrder) {
            $vendorShop = $this->_vendorHelper->getVendorStoreName($vendorOrder->getVendorId());
            if (!$vendorShop) {
                $vendor = $this->_vendorModel->create()->load($vendorOrder->getVendorId());
                $vendorShop = $vendor->getVendorId();
            }
            $vendorShop = $vendorShop ? $vendorShop : $vendor->getVendorId();
            $description .= '<strong>'.$vendorShop.": </strong>".$vendorOrder->getData(OrderInterface::SHIPPING_DESCRIPTION)." <br /> ";
        }
        $description = trim($description);
        $description = trim($description, "|");
        if (!$description) {
            $description = $subject->getData(OrderInterface::SHIPPING_DESCRIPTION);
        }

        return $description;
    }
}
