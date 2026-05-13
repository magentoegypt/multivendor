<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Plugin\Controller;

use Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Router
{
    /** var \Magento\Framework\App\ActionFactory */
    protected $actionFactory;

    /** @var \Magento\Framework\UrlInterface */
    protected $url;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var UrlFinderInterface */
    protected $urlFinder;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\ActionFactory       $actionFactory
     * @param \Magento\Framework\UrlInterface            $url
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param UrlFinderInterface                         $urlFinder
     */
    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        UrlFinderInterface $urlFinder,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->actionFactory = $actionFactory;
        $this->url = $url;
        $this->storeManager = $storeManager;
        $this->urlFinder = $urlFinder;
        $this->scopeConfig = $scopeConfig;
    }

    public function aroundMatch(\Vnecoms\VendorsPage\Controller\Router $router, $proceed, \Magento\Framework\App\RequestInterface $request)
    {
        //before match
        //match
        $result = $proceed($request);

        return $result;

        $vendor = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\Registry')->registry('vendor');

        if (!$vendor) {
            return $result;
        }

        $categoryModel = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Vnecoms\VendorsCategory\Model\Category');

        $rootId = $categoryModel->getRootId($vendor->getId());

        $rootCategory = $categoryModel->load($rootId);

        if (!\Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\Registry')->registry('current_root_cat')) {
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get('Magento\Framework\Registry')->register('current_root_cat', $rootCategory);
        }

        $baseUrlPage = $this->scopeConfig->getValue(
            'vendors/vendorspage/url_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

      //  echo $baseUrlPage;die();
        $combine = ($baseUrlPage !== null) ? $baseUrlPage.'/'.$vendor->getVendorId() : $vendor->getVendorId();

        $requestPathInfo = str_replace('vendorspage', $combine, $request->getPathInfo());
        $requestPathInfo = str_replace('index', '', $requestPathInfo);

       // var_dump($requestPathInfo);die();
        $rewrite = $this->getRewrite($requestPathInfo, $this->storeManager->getStore()->getId());

       // var_dump($rewrite);die();
        if ($rewrite === null) {
            return $result;
        }

        if ($rewrite !== null) {
            if ($rewrite->getRedirectType()) {
                return $this->processRedirect($request, $rewrite);
            }

            $request->setAlias(\Magento\Framework\UrlInterface::REWRITE_REQUEST_PATH_ALIAS, $rewrite->getRequestPath());
            $request->setPathInfo('/'.$rewrite->getTargetPath());

            return $this->actionFactory->create('Magento\Framework\App\Action\Forward');
        }
    }

    /**
     * @param string $requestPath
     * @param int    $storeId
     *
     * @return UrlRewrite|null
     */
    protected function getRewrite($requestPath, $storeId)
    {
        return $this->urlFinder->findOneByData([
            UrlRewrite::REQUEST_PATH => trim($requestPath, '/'),
            UrlRewrite::STORE_ID => $storeId,
        ]);
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param UrlRewrite                              $rewrite
     *
     * @return \Magento\Framework\App\ActionInterface|null
     */
    protected function processRedirect($request, $rewrite)
    {
        $target = $rewrite->getTargetPath();
        if ($rewrite->getEntityType() !== Rewrite::ENTITY_TYPE_CUSTOM
            || ($prefix = substr($target, 0, 6)) !== 'http:/' && $prefix !== 'https:'
        ) {
            $target = $this->url->getUrl('', ['_direct' => $target]);
        }

        return $this->redirect($request, $target, $rewrite->getRedirectType());
    }
}
