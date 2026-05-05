<?php
namespace MagentoEgypt\VendorExtend\Block;

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

    protected $menuItesm;
    protected $menuHtml;

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

    public function getTemplate()
    {
        return 'MagentoEgypt_VendorExtend::html/navigation.phtml';
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
        if($this->menuItesm == null) {
            $this->menuItesm = $this->categoryCollectionFactory->create();
            $this->menuItesm->addFieldToFilter('included_in_menu', 1);
            $this->menuItesm->addFieldToFilter('level', ['gt' => 0]);
            $this->menuItesm->addIsActiveFilter();
            $this->menuItesm->addVendorFilter($this->_getCurrentVendor());
            $this->menuItesm->addOrder('level', Collection::SORT_ORDER_ASC);
            $this->menuItesm->addOrder('position', Collection::SORT_ORDER_ASC);
            $this->menuItesm->addOrder('parent_id', Collection::SORT_ORDER_ASC);
            $this->menuItesm->addOrder('category_id', Collection::SORT_ORDER_ASC);
        }
        
        return $this->menuItesm;
	}
    
    public function getMenuHtml() {
        if($this->menuHtml == null) {
            $this->menuHtml = '';
            foreach($this->menuItesm as $item) {
                if($item->getLevel() == 1) {
                    $this->menuHtml .= '<li class="level'.($item->getLevel()-1).' menu-1columns category-menu mmegamenu-'.$item->getCategoryId().'">';
                    $this->menuHtml .= '<a href="'.$item->getUrl().'" class="level0"><span>'.$item->getName().'</span></a>';
                    $this->menuHtml .= $this->getSubMenu($item->getId(), true);
                    $this->menuHtml .= '</li>';
                }
            }
        }
        // die('MAS');
        return $this->menuHtml;
    }

    protected function getSubMenu($parentId, $isSub) {
        $menuHtml = [];
        $dd = '<span class="toggle-menu"><span class="icon-toggle"></span></span>';
        foreach($this->menuItesm as $item) {
            if($item->getParentId() == $parentId) {
                $menuHtml[] = '<li class="level'.($item->getLevel()-1).' dropdown-submenu">';
                $menuHtml[] = '<a href="'.$item->getUrl().'">'.$item->getName().'</a>';
                $menuHtml[] = $this->getSubMenu($item->getId(), false);
                $menuHtml[] = '</li>';
            }
        }
        $finalHtml = '';
        if(count($menuHtml) > 0) {
            $finalHtml = implode('',$menuHtml);
            $finalHtml = $dd.
                ($isSub ? '<ul class="dropdown-mega-menu"><li>' : '').
                '<ul class="dropdown-submenu-ct">'.$finalHtml.'</ul>'.
                ($isSub ? '</ll></ui>' : '');

        }
        return $finalHtml;
    }
	
	/**
     * get current vendor.
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    protected function _getCurrentVendor()
    {
        return $this->_registry->registry('current_vendor_domain')->getVendor();
    }
}

