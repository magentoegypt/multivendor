<?php

namespace Vnecoms\VendorsConfigApproval\Observer;

use Magento\Framework\Event\ObserverInterface;

class PlushCacheVendorPage implements ObserverInterface
{
    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $vendorFactory;

    /**
     * FieldsetPrepareBefore constructor.
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     */
    public function __construct(
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
    ) {
        $this->vendorFactory = $vendorFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $vendorId = $observer->getVendorId();
        $vendor = $this->vendorFactory->create()->load($vendorId);
        $vendor->cleanCache();
    }

}
