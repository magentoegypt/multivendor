<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Block;

use Vnecoms\VendorsCategory\Model\Category;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\TreeFactory;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;

/**
 * Plugin for top menu block.
 */
class Menu extends Template implements IdentityInterface
{
    /**
     * Catalog category.
     *
     * @var \Vnecoms\VendorsCategory\Helper\Category
     */
    protected $catalogCategory;

    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory
     */
    private $collectionFactory;

    /**
     * Top menu data tree.
     *
     * @var \Magento\Framework\Data\Tree\Node
     */
    protected $_menu;

    /**
     * Cache identities.
     *
     * @var array
     */
    protected $identities = [];

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $_objectManager;

//    public function __construct(
//        \Vnecoms\VendorsCategory\Helper\Category $catalogCategory,
//        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
//        Template\Context $context,
//        NodeFactory $nodeFactory,
//        TreeFactory $treeFactory,
//        \Magento\Framework\App\ObjectManager $objectManager,
//        $data = []
//    ) {
//        parent::__construct($context, $data);
//        $this->catalogCategory = $catalogCategory;
//        $this->collectionFactory = $categoryCollectionFactory;
//        $this->_objectManager = $objectManager;
//        $this->_menu = $nodeFactory->create(
//            [
//                'data' => [],
//                'idField' => 'root',
//                'tree' => $treeFactory->create()
//            ]
//        );
//    }

    public function __construct(
        \Vnecoms\VendorsCategory\Helper\Category $catalogCategory,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        Template\Context $context,
        NodeFactory $nodeFactory,
        TreeFactory $treeFactory,
        array $data = []
    ) {
        $this->catalogCategory = $catalogCategory;
        $this->collectionFactory = $categoryCollectionFactory;
        parent::__construct($context, $data);
        $this->_menu = $nodeFactory->create(
            [
                'data' => [],
                'idField' => 'root',
                'tree' => $treeFactory->create(),
            ]
        );
    }

    /**
     * Add identity.
     *
     * @param array $identity
     */
    public function addIdentity($identity)
    {
        if (!in_array($identity, $this->identities)) {
            $this->identities[] = $identity;
        }
    }

    public function getIdentities()
    {
        $this->addIdentity(Category::CACHE_TAG);

        /* @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
       // $collection = $this->getCategoryTree($storeId, $rootId);

        return $this->identities;
    }

    /**
     * Get current category
     * If not select any category, return root category (like main vendor shop page).
     *
     * @return \Vnecoms\VendorsCategory\Model\Category
     */
    private function getCurrentCategory()
    {
        $currentCategory = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Registry')->registry('current_vendor_category');

        if ($currentCategory and $currentCategory->getId()) {
            return $currentCategory;
        }

        return \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Registry')->registry('current_root_cat');
    }

    /**
     * Convert category to array.
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param \Magento\Catalog\Model\Category $currentCategory
     *
     * @return array
     */
    private function getCategoryAsArray($category, $currentCategory)
    {
        return [
            'name' => $category->getName(),
            'id' => 'category-node-'.$category->getId(),
            'url' => $this->catalogCategory->getCategoryUrl($category),
            'has_active' => in_array((string) $category->getId(), explode('/', $currentCategory->getPath()), true),
            'is_active' => $category->getId() == $currentCategory->getId(),
            'product_count' => $category->getProductCount(true, true, true),
        ];
    }

    /**
     * Get Category Tree.
     *
     * @param int $rootId
     *
     * @return \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCategoryTree($rootId)
    {
        /** @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('path', ['like' => $rootId.'/%']); //load only from store root
        $collection->addFieldToFilter('included_in_menu', 1);
        $collection->addIsActiveFilter();
        $collection->addVendorFilter($this->_getCurrentVendor());
        //$collection->addUrlRewriteToResult();
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
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Registry')->registry('vendor');
    }

    /**
     * Get cache key informative items.
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $keyInfo = parent::getCacheKeyInfo();
        $keyInfo[] = $this->getUrl('*/*/*', ['_current' => true, '_query' => '']);

        return $keyInfo;
    }

    /**
     * Get tags array for saving cache.
     *
     * @return array
     */
    protected function getCacheTags()
    {
        return array_merge(parent::getCacheTags(), $this->getIdentities());
    }

    /**
     * Get top menu html.
     *
     * @param string $outermostClass
     * @param string $childrenWrapClass
     * @param int    $limit
     *
     * @return string
     */
    public function getHtml($outermostClass = '', $childrenWrapClass = '', $limit = 0)
    {
        $this->_eventManager->dispatch(
            'vendor_page_category_before_html',
            ['menu' => $this->_menu, 'block' => $this]
        );

        $rootId = $this->_getRootCategoryId();
        $collection = $this->getCategoryTree($rootId);
        $currentCategory = $this->getCurrentCategory();
        $mapping = [$rootId => $this->_menu];  // use nodes stack to avoid recursion
        foreach ($collection as $category) {
            if (!isset($mapping[$category->getParentId()])) {
                continue;
            }
            /** @var Node $parentCategoryNode */
            $parentCategoryNode = $mapping[$category->getParentId()];

            $categoryNode = new Node(
                $this->getCategoryAsArray($category, $currentCategory),
                'id',
                $parentCategoryNode->getTree(),
                $parentCategoryNode
            );
            $parentCategoryNode->addChild($categoryNode);
            $mapping[$category->getId()] = $categoryNode; //add node in stack
        }
        $this->_menu->addData($mapping);

      //  var_dump($this->_menu->getData());die();

        $this->_menu->setOutermostClass($outermostClass);
        $this->_menu->setChildrenWrapClass($childrenWrapClass);

        $html = $this->_getHtml($this->_menu, $childrenWrapClass, $limit);
//var_dump($this->_menu->getData());
    //    die();
        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);
        $this->_eventManager->dispatch(
            'vendor_page_category_after_html',
            ['menu' => $this->_menu, 'transportObject' => $transportObject]
        );
        $html = $transportObject->getHtml();

        return $html;
    }

    /**
     * Recursively generates top menu html from data that is specified in $menuTree.
     *
     * @param \Magento\Framework\Data\Tree\Node $menuTree
     * @param string                            $childrenWrapClass
     * @param int                               $limit
     * @param array                             $colBrakes
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getHtml(
        \Magento\Framework\Data\Tree\Node $menuTree,
        $childrenWrapClass,
        $limit,
        $colBrakes = []
    ) {
        $html = '';

        $children = $menuTree->getChildren();
        $parentLevel = $menuTree->getLevel();
        $childLevel = $parentLevel === null ? 0 : $parentLevel + 1;

        $counter = 1;
        $itemPosition = 1;
        $childrenCount = $children->count();

        $parentPositionClass = $menuTree->getPositionClass();
        $itemPositionClassPrefix = $parentPositionClass ? $parentPositionClass.'-' : 'nav-';

        foreach ($children as $child) {
            $child->setLevel($childLevel);
            $child->setIsFirst($counter == 1);
            $child->setIsLast($counter == $childrenCount);
            $child->setPositionClass($itemPositionClassPrefix.$counter);

            $outermostClassCode = '';
            $outermostClass = $menuTree->getOutermostClass();

            if ($childLevel == 0 && $outermostClass) {
                $outermostClassCode = ' class="'.$outermostClass.'" ';
                $child->setClass($outermostClass);
            }

            if (count($colBrakes) && $colBrakes[$counter]['colbrake']) {
                $html .= '</ul></li><li class="column"><ul>';
            }

            $html .= '<li '.$this->_getRenderedMenuItemAttributes($child).'>';
            $html .= '<a href="'.$child->getUrl().'" '.$outermostClassCode.'>'.$this->escapeHtml(
                $child->getName()
            ).'<span class="product-count">'.$child->getProductCount().'</span></a>'.$this->_addSubMenu(
                $child,
                $childLevel,
                $childrenWrapClass,
                $limit
            ).'</li>';
            ++$itemPosition;
            ++$counter;
        }

        if (count($colBrakes) && $limit) {
            $html = '<li class="column"><ul>'.$html.'</ul></li>';
        }

        return $html;
    }

    /**
     * Add sub menu HTML code for current menu item.
     *
     * @param \Magento\Framework\Data\Tree\Node $child
     * @param string                            $childLevel
     * @param string                            $childrenWrapClass
     * @param int                               $limit
     *
     * @return string HTML code
     */
    protected function _addSubMenu($child, $childLevel, $childrenWrapClass, $limit)
    {
        $html = '';
        if (!$child->hasChildren()) {
            return $html;
        }

        $colStops = null;
//        if ($childLevel == 0 && $limit) {
//            $colStops = $this->_columnBrake($child->getChildren(), $limit);
//        }

        $displayUl = '';
        if ($childLevel <= 1) {
            $displayUl = 'display:block;';
        } else {
            $displayUl = 'display:none;';
        }
        /*show all category ALL LEVEL */
        $displayUl = 'display:block;';
        $html .= '<ul style="padding-left:15px;'.$displayUl.'" class="level'.$childLevel.' submenu">';
        $html .= $this->_getHtml($child, $childrenWrapClass, $limit);
        $html .= '</ul>';
       // $html .= '<span class="head"><a href="javascript:void(0)"></a></span>';
        return $html;
    }

    /**
     * Count All Subnavigation Items.
     *
     * @param \Magento\Backend\Model\Menu $items
     *
     * @return int
     */
    protected function _countItems($items)
    {
        $total = $items->count();
        foreach ($items as $item) {
            /** @var $item \Magento\Backend\Model\Menu\Item */
            if ($item->hasChildren()) {
                $total += $this->_countItems($item->getChildren());
            }
        }

        return $total;
    }

    /**
     * Get menu object.
     *
     * @return Node
     */
    public function getMenu()
    {
        return $this->_menu;
    }

    /**
     * Get block cache life time.
     *
     * @return int
     */
    protected function getCacheLifetime()
    {
        return parent::getCacheLifetime() ?: 3600;
    }

    /**
     * Generates string with all attributes that should be present in menu item element.
     *
     * @param \Magento\Framework\Data\Tree\Node $item
     *
     * @return string
     */
    protected function _getRenderedMenuItemAttributes(\Magento\Framework\Data\Tree\Node $item)
    {
        $html = '';
        $attributes = $this->_getMenuItemAttributes($item);
        foreach ($attributes as $attributeName => $attributeValue) {
            $html .= ' '.$attributeName.'="'.str_replace('"', '\"', $attributeValue).'"';
        }

        return $html;
    }

    /**
     * Returns array of menu item's attributes.
     *
     * @param \Magento\Framework\Data\Tree\Node $item
     *
     * @return array
     */
    protected function _getMenuItemAttributes(\Magento\Framework\Data\Tree\Node $item)
    {
        $menuItemClasses = $this->_getMenuItemClasses($item);

        return ['class' => implode(' ', $menuItemClasses)];
    }

    /**
     * Returns array of menu item's classes.
     *
     * @param \Magento\Framework\Data\Tree\Node $item
     *
     * @return array
     */
    protected function _getMenuItemClasses(\Magento\Framework\Data\Tree\Node $item)
    {
        $classes = [];

        $classes[] = 'level'.$item->getLevel();
        $classes[] = $item->getPositionClass();

        if ($item->getIsFirst()) {
            $classes[] = 'first';
        }

        if ($item->getIsActive()) {
            $classes[] = 'active';
        } elseif ($item->getHasActive()) {
            $classes[] = 'has-active';
        }

        if ($item->getIsLast()) {
            $classes[] = 'last';
        }

        if ($item->getClass()) {
            $classes[] = $item->getClass();
        }

        if ($item->hasChildren()) {
            $classes[] = 'parent';
        }

        return $classes;
    }

    /**
     * Building Array with Column Brake Stops.
     *
     * @param \Magento\Backend\Model\Menu $items
     * @param int                         $limit
     *
     * @return array|void
     *
     * @todo: Add Depth Level limit, and better logic for columns
     */
    protected function _columnBrake($items, $limit)
    {
        $total = $this->_countItems($items);
        if ($total <= $limit) {
            return;
        }

        $result[] = ['total' => $total, 'max' => (int) ceil($total / ceil($total / $limit))];

        $count = 0;
        $firstCol = true;

        foreach ($items as $item) {
            $place = $this->_countItems($item->getChildren()) + 1;
            $count += $place;

            if ($place >= $limit) {
                $colbrake = !$firstCol;
                $count = 0;
            } elseif ($count >= $limit) {
                $colbrake = !$firstCol;
                $count = $place;
            } else {
                $colbrake = false;
            }

            $result[] = ['place' => $place, 'colbrake' => $colbrake];

            $firstCol = false;
        }

        return $result;
    }

    /**
     * @return int
     */
    protected function _getRootCategoryId()
    {
		$om = \Magento\Framework\App\ObjectManager::getInstance();
		$registry = $om->get('Magento\Framework\Registry');
		if(!$registry->registry('current_root_cat')){
			$categoryModel = $om->create('Vnecoms\VendorsCategory\Model\Category');
			$rootId = $categoryModel->getRootId($this->_getCurrentVendor()->getId());
			$rootCategory = $categoryModel->load($rootId);
			$registry->register('current_root_cat', $rootCategory);
		}

        return $registry->registry('current_root_cat')->getId();
    }

    public function getCategoryHeader()
    {
        $header = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Vnecoms\VendorsConfig\Helper\Data')->getVendorConfig('category/general/header', $this->_getCurrentVendor()->getId());
		return __($header);
    }
}
