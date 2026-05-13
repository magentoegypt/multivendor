<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class SelectAndSellPredispatch implements ObserverInterface
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

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * SelectAndSellPredispatch constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param CollectionFactory $productCollectionFactory
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
            if (!$groupHelper->canUseSelectAndSell($groupId)) {
                $controllerAction = $observer->getControllerAction();

                $this->_redirect->redirect($controllerAction->getResponse(), 'catalog/product');
                $this->messageManager->addErrorMessage("You are in a vendor group that does not allow to use Select And Sell function.");
                $controllerAction->getActionFlag()->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
                return;
            }
        }
    }
}
