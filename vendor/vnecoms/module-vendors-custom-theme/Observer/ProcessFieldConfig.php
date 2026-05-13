<?php

namespace Vnecoms\VendorsCustomTheme\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Helper\Data;
use Vnecoms\VendorsCustomTheme\Helper\Data as Helper;

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
        $vendorGroupId = $this->_vendorSession->getVendor()->getGroupId();
        if (
            !class_exists('Vnecoms\VendorsGroup\Helper\Data') ||
            !$this->moduleManager->isOutputEnabled('Vnecoms_VendorsGroup')
        ) return;

        /** @var \Vnecoms\VendorsGroup\Helper\Data $groupHelper */
        $groupHelper = $this->_objectManager->create('Vnecoms\VendorsGroup\Helper\Data');
        if(
            !$this->getConfigName($observer,'custom_theme') ||
            $groupHelper->getConfig(Helper::XML_PATH_VENDOR_CUSTOM_THEME, $vendorGroupId)
        ) return;

        $transport = $observer->getTransport();
        $html = '<div class="alert alert-danger alert-dismissible">
                <h4><i class="icon fa fa-ban"></i> '.__('Warning !').'</h4>'
            .__('You are not allowed to access this feature.').'
              </div>';
        $transport->setHtml($html);
        $transport->setForceReturn(true);
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
