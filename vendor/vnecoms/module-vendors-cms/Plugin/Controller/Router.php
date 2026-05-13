<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 10/01/2017
 * Time: 00:18.
 */

namespace Vnecoms\VendorsCms\Plugin\Controller;

use Magento\UrlRewrite\Model\UrlFinderInterface;
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

    /** @var  \Magento\Framework\Registry */
    protected $_coreRegistry;

    /** @var \Vnecoms\VendorsCms\Model\Page  */
    protected $cmsPage;

    protected $vendorConfig;

    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        UrlFinderInterface $urlFinder,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\VendorsCms\Model\Page $cmsPage,
        \Vnecoms\VendorsConfig\Helper\Data $vendorConfig
    ) {
        $this->actionFactory = $actionFactory;
        $this->url = $url;
        $this->storeManager = $storeManager;
        $this->urlFinder = $urlFinder;
        $this->scopeConfig = $scopeConfig;
        $this->_coreRegistry = $coreRegistry;
        $this->cmsPage = $cmsPage;
        $this->vendorConfig = $vendorConfig;
    }

    /**
     * @param \Vnecoms\VendorsPage\Controller\Router $router
     * @param $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @return mixed
     */
    public function aroundMatch(\Vnecoms\VendorsPage\Controller\Router $router, $proceed, \Magento\Framework\App\RequestInterface $request)
    {
        $result = $proceed($request); //after match
        $identifier = trim($request->getPathInfo(), '/');
        $pageIdentifier = '';
        $vendor = $this->_coreRegistry->registry('vendor');
        return $result;
        if (!$vendor) {
            return $result;
        }

        if ($vendor !== null) {
            $pageIdentifier = $this->vendorConfig
                ->getVendorConfig(\Vnecoms\VendorsCms\Helper\Page::XML_PATH_HOME_PAGE, $vendor->getId());

            if ($pageIdentifier == null) {
                return $result;
            }
        }

        /*
         * only if vendor set cms homepage, will set controller to vendorscms
         * others default vendor page
         */
        if ($pageIdentifier != null && !$request->getParam('id')) {
            if (trim($request->getPathInfo(), '/') == 'vendorspage/index/index') {
                //$request->setPathInfo('/vendorscms/page/view/');
                $request->setModuleName('vendorscms')->setControllerName('page')
                    ->setActionName('view');
                $request->setParam('vendor_id', $vendor->getId());
                $request->setParam('page_id', $pageIdentifier);
                $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $pageIdentifier);
                return $result;
            }
        }
    }
}
