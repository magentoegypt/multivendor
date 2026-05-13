<?php

namespace Vnecoms\VendorsCms\Observer\Vendors;

use Magento\Framework\Event\ObserverInterface;

class CheckAllowCMSPredispatch implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsCms\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * CheckAllowCMSPredispatch constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Vnecoms\VendorsCms\Helper\Data $helperData
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Magento\Framework\Module\Manager $moduleManager,
        \Vnecoms\VendorsCms\Helper\Data $helperData
    ) {
        $this->helperData = $helperData;
        $this->_objectManager = $objectManager;
        $this->_vendorSession = $vendorSession;
        $this->moduleManager = $moduleManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // TODO: Implement execute() method.
        if (!$this->helperData->isEnabled()) {
        }
        $groupId = $this->_vendorSession->getVendor()->getGroupId();
        if ($this->moduleManager->isOutputEnabled('Vnecoms_VendorsGroup')) {
            $groupHelper = $this->_objectManager->create('Vnecoms\VendorsGroup\Helper\Data');
            if (!$groupHelper->canUseCMS($groupId)) {
                $request = $observer->getRequest();
                $request->initForward();
                $request->setActionName('no-route');
                $request->setDispatched(false);
                return;
            }
        }

    }
}
