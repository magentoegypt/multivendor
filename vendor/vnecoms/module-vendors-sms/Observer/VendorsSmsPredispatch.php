<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class VendorsSmsPredispatch implements ObserverInterface
{
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $_redirect;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    protected $_objectManager;

    protected $moduleManager;
    /**
     * Constructor
     *
     * @param \Vnecoms\VendorsGroup\Helper\Data $groupHelper
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_vendorSession = $vendorSession;
        $this->_redirect = $redirect;
        $this->moduleManager = $moduleManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->messageManager = $messageManager;
    }

    /**
     *
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $groupId = $this->_vendorSession->getVendor()->getGroupId();
        if ($this->moduleManager->isOutputEnabled('Vnecoms_VendorsGroup')) {
            $groupHelper = $this->_objectManager->create('Vnecoms\VendorsGroup\Helper\Data');
            if (!$groupHelper->canUseVendorSMS($groupId)) {
                $controllerAction = $observer->getControllerAction();

                $this->_redirect->redirect($controllerAction->getResponse(), 'dashboard');
                $this->messageManager->addErrorMessage("You are in a vendor group that does not allow to use SMS function.");
                $controllerAction->getActionFlag()->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
                return;
            }
        }
    }
}
