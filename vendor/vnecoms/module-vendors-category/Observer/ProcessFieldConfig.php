<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCategory\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Helper\Data;

class ProcessFieldConfig implements ObserverInterface
{
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
     * ProcessFieldConfig constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_vendorSession = $vendorSession;
        $this->moduleManager = $moduleManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $groupId = $this->_vendorSession->getVendor()->getGroupId();
        if ($this->moduleManager->isOutputEnabled('Vnecoms_VendorsGroup')) {
            $groupHelper = $this->_objectManager->create('Vnecoms\VendorsGroup\Helper\Data');
            if(!$groupHelper->canUseCategory($groupId) && $this->getConfigName($observer,'category')){
                $transport = $observer->getTransport();

                $html = 'You are in a Vendor group that is not allowed to use this module! <br>';
                $transport->setHtml($html);
                $transport->setForceReturn(true);
            }
        }
    }

    public function getConfigName(\Magento\Framework\Event\Observer $observer, $name){
        $transport = $observer->getTransport();
        foreach ($transport->getFieldset()->getElements() as $field){
            if(strpos((string)$field->getId(), $name) !== false){
                return true;
            }
        }
        return false;
    }
}
