<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPage\Controller;

class Router implements \Magento\Framework\App\RouterInterface
{
    /**
     * @var \Magento\Framework\App\ActionFactory
     */
    protected $actionFactory;

    /**
     * Event manager
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Config primary
     *
     * @var \Magento\Framework\App\State
     */
    protected $_appState;

    /**
     * Url
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * Response
     *
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $_response;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;
    
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $_vendorPageHelper;
    
    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;
    
    /**
     * @param \Magento\Framework\App\ActionFactory $actionFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Cms\Model\PageFactory $pageFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResponseInterface $response
     */
    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\UrlInterface $url,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\ResponseInterface $response,
        \Vnecoms\Vendors\Helper\Data $vendorHelper
    ) {
        $this->actionFactory = $actionFactory;
        $this->_eventManager = $eventManager;
        $this->_url = $url;
        $this->_storeManager = $storeManager;
        $this->_vendorFactory = $vendorFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_response = $response;
        $this->_vendorPageHelper = $vendorPageHelper;
        $this->_vendorHelper = $vendorHelper;
    }

    /**
     * Validate and Match Cms Page and modify request
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    public function match(\Magento\Framework\App\RequestInterface $request)
    {
        /* Do nothing if the extension is not enabled.*/
        if (!$this->_vendorHelper->moduleEnabled()) {
            return;
        }
        $identifier = trim($request->getPathInfo(), '/');
        $condition = new \Magento\Framework\DataObject(['identifier' => $identifier, 'continue' => true, 'alias' => false]);
        
        $this->_eventManager->dispatch(
            'vendorspage_controller_router_match_before',
            ['router' => $this, 'condition' => $condition, 'request' => $request]
        );
        
        $identifier = $condition->getIdentifier();

        if ($condition->getRedirectUrl()) {
            $this->_response->setRedirect($condition->getRedirectUrl());
            $request->setDispatched(true);
            return $this->actionFactory->create('Magento\Framework\App\Action\Redirect');
        }

        if (!$condition->getContinue()) {
            return null;
        }

        if ($urlKey = $this->_vendorPageHelper->getUrlKey()) {
            //check if we have reserved word in the url
            $pageIds        = explode('/', $identifier, 3);
            $handle         = isset($pageIds[0])?$pageIds[0]:'';
            $vendorId       = isset($pageIds[1])?$pageIds[1]:'';
            $requestPath    = isset($pageIds[2])?$pageIds[2]:'';
        
            if (!(trim($handle) == $urlKey)) {
                return false;
            }
        } else {
            $pageIds = explode('/', $identifier, 2);
            $vendorId       = isset($pageIds[0])?$pageIds[0]:'';
            $requestPath    = isset($pageIds[1])?$pageIds[1]:'';
        }

        $currentWebsiteId = $this->_storeManager->getStore()->getWebsiteId();
        $vendor = $this->_vendorFactory->create()->loadByIdentifier($vendorId);
        if (!$vendor->getId() || $vendor->getStatus() != \Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED
            || $currentWebsiteId != $vendor->getWebsiteId()

        ) {
            return null;
        }
        
        if (!$this->_coreRegistry->registry('vendor_id')) {
            $this->_coreRegistry->register('vendor_id', $vendorId);
            $this->_coreRegistry->register('vendor', $vendor);
            $this->_coreRegistry->register('current_vendor', $vendor);
        }
        
        $condition->setData('request_path', $requestPath);
        $condition->setData('vendor', $vendor);
        $condition->setData('vendor_id', $vendorId);
        
        $this->_eventManager->dispatch(
            'vendorspage_controller_router_match_vendor',
            ['router' => $this, 'condition' => $condition, 'request' => $request]
        );
        
        if (!$condition->getContinue()) {
            return null;
        }
        
        $targetPath = $condition->getRequestPath();
        $targetPath = explode('/', $targetPath, 3);
        
        $controller     = isset($targetPath[0]) && $targetPath[0]?$targetPath[0]:'index';
        $action = isset($targetPath[1]) && $targetPath[1]?$targetPath[1]:'index';
        $params = isset($targetPath[2])?$targetPath[2]:'';
        
        $request->setPathInfo('/vendorspage/'.$controller.'/'.$action.'/'.$params);
        $request->setParam('vendor_id', $vendorId);
        $alias = $condition->getAlias()!== false?$condition->getAlias():$identifier;
        $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $alias);

        return $this->actionFactory->create('Magento\Framework\App\Action\Forward');
    }
}
