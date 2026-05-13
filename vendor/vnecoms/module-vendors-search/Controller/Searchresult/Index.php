<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsSearch\Controller\Searchresult;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
//use Magento\Search\Model\QueryFactory;
use Vnecoms\VendorsSearch\Model\QueryFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Catalog session.
     *
     * @var Session
     */
    protected $_catalogSession;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var QueryFactory
     */
    private $_queryFactory;

    /**
     * Catalog Layer Resolver.
     *
     * @var Resolver
     */
    private $layerResolver;

    protected $_coreRegistry;

    /** @var \Vnecoms\VendorsPage\Helper\Data  */
    protected $_vendorPageHelper;

    /**
     * Index constructor.
     * @param Context $context
     * @param Session $catalogSession
     * @param StoreManagerInterface $storeManager
     * @param QueryFactory $queryFactory
     * @param Resolver $layerResolver
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper
     */
    public function __construct(
        Context $context,
        Session $catalogSession,
        StoreManagerInterface $storeManager,
        QueryFactory $queryFactory,
        Resolver $layerResolver,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_catalogSession = $catalogSession;
        $this->_queryFactory = $queryFactory;
        $this->layerResolver = $layerResolver;
        $this->_coreRegistry = $registry;
        $this->_vendorPageHelper  = $vendorPageHelper ;
    }

    /**
     * Display search result.
     */
    public function execute()
    {
        if (!\Magento\Framework\App\ObjectManager::getInstance()->get('Vnecoms\VendorsSearch\Helper\Data')->isEnabled()) {
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
        }
        if (!$this->_coreRegistry->registry('vendor')) {
            return $this->_forward('no-route');
        }
        $this->layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);

        $query = $this->_queryFactory->get();
        $query->setStoreId($this->_storeManager->getStore()->getId());

        if ($query->getQueryText() != '') {
            if ($this->_objectManager->get('Vnecoms\VendorsSearch\Helper\Data')->isMinQueryLength()) {
                $query->setId(0)->setIsActive(1)->setIsProcessed(1);
            } else {
                $query->saveIncrementalPopularity();

                if ($query->getRedirect()) {
                    $this->getResponse()->setRedirect($query->getRedirect());

                    return;
                }
            }
            $this->_objectManager->get('Vnecoms\VendorsSearch\Helper\Data')->checkNotes();

            $this->_view->loadLayout();
            $this->_view->renderLayout();
        } else {
            $vendor = $this->getVendor();
            $vendorUrl = $this->_vendorPageHelper->getUrl($vendor);
            //$this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($vendorUrl));
            $this->getResponse()->setRedirect($vendorUrl);
        }
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }
}
