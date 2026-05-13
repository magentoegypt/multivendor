<?php
namespace Vnecoms\VendorsSms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsSms\Helper\Data;

class CustomerSaveAfter implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsSms\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Email\Model\Template\Filter
     */
    protected $customerFactory;

    /**
     * @var \Vnecoms\VendorsConfig\Model\Config
     */
    protected $vendorConfig;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $vendorFactory;

    /**
     * CustomerSaveAfter constructor.
     * @param Data $helper
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Vnecoms\VendorsConfig\Model\ConfigFactory $vendorConfig
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     */
    public function __construct(
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Vnecoms\VendorsConfig\Model\ConfigFactory $vendorConfig,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
    ){
        $this->helper = $helper;
        $this->customerFactory = $customerFactory;
        $this->vendorConfig = $vendorConfig;
        $this->vendorFactory = $vendorFactory;
    }

    /**
     * Vendor Save After
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();
        $customerObject = $this->customerFactory->create()->load($customer->getId());
        $vendor = $this->vendorFactory->create()->loadByCustomer($customerObject);
        if (!$vendor->getId()) return;

        if($customerObject->getId() && $customerObject->getData("mobilenumber")) {
            $config = $this->vendorConfig->create()->getCollection()->addFieldToFilter("vendor_id", $vendor->getId())
                ->addFieldToFilter("path", Data::XML_PATH_VENDOR_MOBILE)->getFirstItem();

            if (!$config->getId()) {
                $this->vendorConfig->create()->setData([
                    "vendor_id" => $vendor->getId(),
                    "path" => Data::XML_PATH_VENDOR_MOBILE,
                    "value" => $customerObject->getData("mobilenumber"),
                    "store" => 0
                ])->save();
            } else {
                $config->setData("value", $customerObject->getData("mobilenumber"))->save();
            }
        }

        return $this;
    }
}
