<?php

namespace Vnecoms\VendorsAvatarProfile\Block\Attributes;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\ObjectManagerInterface;

class Avatar extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Magento\Framework\View\Element\AbstractBlock
     */
    protected $viewFileUrl;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Vnecoms\VendorsAvatarProfile\Helper\Data 
     */
    protected $helperData;

    /**
     * Avatar constructor.
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Vnecoms\VendorsAvatarProfile\Helper\Data $helperData
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Customer $customer,
        \Vnecoms\VendorsAvatarProfile\Helper\Data $helperData
    ) {
        $this->objectManager = $objectManager;
        $this->customer = $customer;
        $this->helperData = $helperData;
        parent::__construct($context);
    }

    /**
     * @param $file
     * @return string
     */
    public function getAvatarCurrentCustomer($file)
    {
        return $this->helperData->getAvatarOfCustomer($file);
    }

    /**
     * @param $customer_id
     * @return string
     */
    public function getCustomerAvatarById($customer_id)
    {
        $customerDetail = $this->customer->load($customer_id);
        $file = $customerDetail ? $customerDetail->getProfilePicture() : false;
        return $this->helperData->getAvatarOfCustomer($file);
    }
}
