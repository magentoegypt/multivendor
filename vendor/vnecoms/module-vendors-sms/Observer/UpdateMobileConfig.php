<?php
namespace Vnecoms\VendorsSms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsSms\Helper\Data;

class UpdateMobileConfig implements ObserverInterface
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
     * UpdateMobileConfig constructor.
     * @param \Vnecoms\VendorsSms\Helper\Data $helper
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Vnecoms\VendorsConfig\Model\ConfigFactory $vendorConfig
    ){
        $this->helper = $helper;
        $this->customerFactory = $customerFactory;
        $this->vendorConfig = $vendorConfig;
    }

    /**
     * Vendor Save After
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $vendor = $observer->getVendor();
        $customerId = $vendor->getResource()->getRelatedCustomerIdByVendorId($vendor->getId());

        $customerObject = $this->customerFactory->create()->load($customerId);

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
            }
        }

        return $this;
    }
}
