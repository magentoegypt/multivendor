<?php
namespace Vnecoms\VendorsCoupon\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Vnecoms\VendorsCoupon\Helper\Data as Helper;

class CheckVendorsMenu implements ObserverInterface
{
    protected $_vendorSession;

    /**
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     */
    public function __construct(
        \Vnecoms\Vendors\Model\Session $vendorSession
    ) {
        $this->_vendorSession = $vendorSession;
    }

    /**
     *
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!class_exists('Vnecoms\VendorsGroup\Helper\Data')) return;
        $vendorGroupId = $this->_vendorSession->getVendor()->getGroupId();
        /** @var \Vnecoms\VendorsGroup\Helper\Data $groupHelper */
        $groupHelper = ObjectManager::getInstance()->get('Vnecoms\VendorsGroup\Helper\Data');
        if (
            (strpos($observer->getResource(), 'VendorsCoupon') !== false) &&
            !$groupHelper->getConfig(Helper::XML_PATH_VENDOR_COUPON, $vendorGroupId)
        ) {
            $observer->getResult()->setIsAllowed(false);
        }
    }
}
