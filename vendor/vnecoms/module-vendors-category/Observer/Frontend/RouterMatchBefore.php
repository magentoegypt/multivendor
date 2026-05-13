<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\VendorsCategory\Observer\Frontend;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\Vendors\Model\Vendor;

class RouterMatchBefore implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $_vendorPageHelper;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;

    protected $_vendorCategoryFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $setup
     */
    protected $setup;

    /**
     * @var \Magento\Framework\App\ActionFactory
     */
    protected $actionFactory;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    protected $moduleManager;

    /**
     * RouterMatchBefore constructor.
     * @param \Vnecoms\VendorsPage\Helper\Data $data
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Vnecoms\VendorsCategory\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection $connection
     * @param \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $collection
     * @param \Magento\Framework\App\ActionFactory $actionFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct
    (
        \Vnecoms\VendorsPage\Helper\Data $data,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Vnecoms\VendorsCategory\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $connection,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $collection,
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Framework\Registry $registry
    )
    {
        $this->_vendorFactory = $vendorFactory;
        $this->_vendorPageHelper = $data;
        $this->request = $request;
        $this->_vendorCategoryFactory = $categoryFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->_resource = $connection;
        $this->setup = $collection;
        $this->actionFactory = $actionFactory;
        $this->_coreRegistry = $registry;
    }

    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }

    protected function hasDomain()
    {
        return $this->_coreRegistry->registry('vnecoms_vendorsdomain_processed');
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $condition = $observer->getCondition();
        $categoryPath = $condition->getRequestPath();
        $vendor = $this->_coreRegistry->registry('vendor');

        if (!$this->_coreRegistry->registry('current_root_cat')) {
            $categoryModel = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\VendorsCategory\Model\Category');

            $rootId = $categoryModel->getRootId($vendor->getId());

            $rootCategory = $categoryModel->load($rootId);

            $this->_coreRegistry->register('current_root_cat', $rootCategory);
        }

        $suffix = $this->scopeConfig->getValue(
            'catalog/seo/category_url_suffix',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
        $categoryPathAr = explode('/', $categoryPath);
        $mainCatId = $categoryPathAr[count($categoryPathAr)-1];

        if ($suffix) {
            $mainCatId = substr($mainCatId, 0,strlen($mainCatId)-strlen($suffix));
        } else {
            $mainCatId = substr($mainCatId, 0,strlen($mainCatId));
        }


        $categoryLevel = count($categoryPathAr);

        if (!$mainCatId) return;
        $select = $this->_resource->getConnection()->select()->from(
            $this->setup->getTable('ves_vendorscategory_category'),
            'category_id'
        )->where(
            'vendor_id = ?',
            (int) $vendor->getId()
        )->where(
            'url_key = ?',
            $mainCatId
        )->where(
            'level >= ?',
            $categoryLevel
        );

        $catId = $this->_resource->getConnection()->fetchOne($select);

        //echo $select;exit;
        if ($catId) {
            $targetPath = $categoryPath;
            $targetPath = explode('/', $targetPath, 3);

            $controller = 'category';
            $action = 'view';
            $params = isset($targetPath[2])?$targetPath[2]:'';

            // $this->request->setPathInfo('/vendorspage/'.$controller.'/'.$action.'/id/'.$catId);
            $this->request->setModuleName('vendorspage')->setControllerName($controller)
                ->setActionName($action)
                ->setParam('id', $catId);

            return;
        }
    }

    /**
     */

    public function processMatchBeforeDomain($observer){
        $condition = $observer->getCondition();
        $identifier = $condition->getIdentifier();

        $vendor = $this->_coreRegistry->registry('vendor');
        if (!$this->_coreRegistry->registry('current_root_cat')) {
            $categoryModel = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\VendorsCategory\Model\Category');

            $rootId = $categoryModel->getRootId($vendor->getId());

            $rootCategory = $categoryModel->load($rootId);

            $this->_coreRegistry->register('current_root_cat', $rootCategory);
        }
    }
}
