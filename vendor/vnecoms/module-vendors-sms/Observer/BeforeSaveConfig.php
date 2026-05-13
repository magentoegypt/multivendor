<?php
namespace Vnecoms\VendorsSms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsSms\Helper\Data;

class BeforeSaveConfig implements ObserverInterface
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
     * @var \Vnecoms\Sms\Helper\Data
     */
    protected $smsHelper;

    /**
     * BeforeSaveConfig constructor.
     * @param Data $helper
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Vnecoms\VendorsConfig\Model\ConfigFactory $vendorConfig
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Vnecoms\Sms\Helper\Data $smsHelper
     */
    public function __construct(
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Vnecoms\VendorsConfig\Model\ConfigFactory $vendorConfig,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Vnecoms\Sms\Helper\Data $smsHelper
    ){
        $this->helper = $helper;
        $this->customerFactory = $customerFactory;
        $this->vendorConfig = $vendorConfig;
        $this->vendorFactory = $vendorFactory;
        $this->smsHelper = $smsHelper;
    }

    /**
     * Vendor Save After
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->smsHelper->isUniqueMobileNumber()) return;

        $vendorId = $observer->getVendorId();
        $groups = $observer->getGroups();

        $vendor = $this->vendorFactory->create()->load($vendorId);
        if (!$vendor->getId() || !isset($groups['general']['fields']['mobile']['value'])) return;

        $mobileNum = $groups['general']['fields']['mobile']['value'];

        $customerId = $vendor->getResource()->getRelatedCustomerIdByVendorId($vendor->getId());
        $customerObject = $this->customerFactory->create()->load($customerId);

        $collection = $this->customerFactory->create()->getCollection()
            ->addAttributeToFilter('mobilenumber', $mobileNum)
            ->addAttributeToFilter('entity_id', ['neq' => $customerObject->getId()]);

        if($collection->count()){
            throw new \Magento\Framework\Exception\LocalizedException(__("The mobile number is used by another vendor account."));
        }

        if($customerObject->getId()) {
            $customerResource = $customerObject->getResource();
            $customerResource->getConnection()->update(
                $customerResource->getTable('customer_entity'),
                ['mobilenumber' => $mobileNum],
                $customerResource->getConnection()->quoteInto('entity_id = ?', $customerObject->getId())
            );
        }

        return $this;
    }
}
