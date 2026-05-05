<?php
namespace MagentoEgypt\PortoExtend\Block;

use Magento\Framework\Data\Collection;

class Megamenu extends \Magento\Framework\View\Element\Template
{
	/**
     * Current category key
     *
     * @var string
     */
    protected $_currentCategoryKey;

    /**
     * Catalog category
     *
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_catalogCategory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * Customer session
     *
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    protected $_storeManager;

    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;


	public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Store\Model\StoreManagerInterface $storeManager,  
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->httpContext = $httpContext;
        $this->_registry = $registry;
        $this->_storeManager = $storeManager; 
        parent::__construct($context, $data);
    }

    /**
     * Get current category key
     *
     * @return string
     */
    public function getCurrentCategoryKey()
    {
        if (!$this->_currentCategoryKey) {
            $category = $this->getCategory();
            if ($category) {
                $this->_currentCategoryKey = $category->getPath();
            } else {
                $this->_currentCategoryKey = $this->_storeManager->getStore()->getRootCategoryId().'/'.time();
            }
        }

        return $this->_currentCategoryKey;
    }

    /**
     * Get current category
     *
     * @return Category
     */
    public function getCategory()
    {
        $currentCategory = $this->_registry->registry('current_vendor_category');

        if ($currentCategory and $currentCategory->getId()) {
            return $currentCategory;
        }

        return $this->_registry->registry('current_root_cat');
    }

    /**
     * Get Key pieces for caching block content
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $shortCacheId = [
            'CATALOG_NAVIGATION',
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(Context::CONTEXT_GROUP),
            'template' => $this->getTemplate(),
            'name' => $this->getNameInLayout(),
            $this->getCurrentCategoryKey(),
            $this->_getCurrentVendor()->getId()
        ];
        $cacheId = $shortCacheId;

        $shortCacheId = array_values($shortCacheId);
        $shortCacheId = implode('|', $shortCacheId);
        $shortCacheId = md5($shortCacheId);

        $cacheId['category_path'] = $this->getCurrentCategoryKey();
        $cacheId['short_cache_id'] = $shortCacheId;

        return $cacheId;
    }

	public function getMegamenuItems(){
		$collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('included_in_menu', 1);
        $collection->addFieldToFilter('level', 1);
        $collection->addIsActiveFilter();
        $collection->addVendorFilter($this->_getCurrentVendor());
		$collection->addOrder('level', Collection::SORT_ORDER_ASC);
        $collection->addOrder('position', Collection::SORT_ORDER_ASC);
        $collection->addOrder('parent_id', Collection::SORT_ORDER_ASC);
        $collection->addOrder('category_id', Collection::SORT_ORDER_ASC);

		return $collection;
	}
	
	/**
     * get current vendor.
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    protected function _getCurrentVendor()
    {
        return $this->_registry->registry('vendor');
    }
}

