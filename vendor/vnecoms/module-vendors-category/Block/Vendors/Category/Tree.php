<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

/**
 * Categories tree block.
 */

namespace Vnecoms\VendorsCategory\Block\Vendors\Category;

use Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection;
use Magento\Framework\Data\Tree\Node;

/**
 * Class Tree.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Tree extends AbstractCategory
{
    /**
     * @var string
     */
    protected $_template = 'category/tree.phtml';

    /**
     * @var \Magento\Framework\DB\Helper
     */
    protected $_resourceHelper;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    protected $helper;

    /**
     * Tree constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                    $context
     * @param \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Tree $categoryTree
     * @param \Magento\Framework\Registry                                $registry
     * @param \Vnecoms\VendorsCategory\Model\CategoryFactory             $categoryFactory
     * @param \Magento\Framework\Json\EncoderInterface                   $jsonEncoder
     * @param \Magento\Framework\DB\Helper                               $resourceHelper
     * @param \Vnecoms\Vendors\Model\Session                             $vendorSession
     * @param array                                                      $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Tree $categoryTree,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsCategory\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Vnecoms\VendorsCategory\Helper\Data $helper,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_resourceHelper = $resourceHelper;
        $this->helper = $helper;
        parent::__construct($context, $categoryTree, $registry, $categoryFactory, $vendorSession,  $data);
    }

    /**
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setUseAjax(0);
    }


    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $addUrl = $this->getUrl('*/*/add', ['_current' => false, 'id' => null, '_query' => false]);

        //enable this only if admin setting is yes

        if ($this->helper->getEnabledVendorSubCat()) {
            $this->addChild(
                'add_sub_button',
                'Magento\Backend\Block\Widget\Button',
                [
                    'label' => __('Add Subcategory'),
                    'onclick' => "addNew('" . $addUrl . "', false)",
                    'class' => 'bg-purple',
                    'id' => 'add_subcategory_button',
                    'style' => $this->canAddSubCategory() ? '' : 'display: none;',
                ]
            );
        }
        if ($this->canAddRootCategory()) {
            $this->addChild(
                    'add_root_button',
                    'Magento\Backend\Block\Widget\Button',
                    [
                        'label' => __('Add New Category'),
                        'onclick' => "addNew('".$addUrl."', true)",
                        'class' => 'btn-primary',
                        'id' => 'add_root_category_button',
                    ]
                );
        }

        return parent::_prepareLayout();
    }

    /**
     * Retrieve list of categories with name containing $namePart and their parents.
     *
     * @param string $namePart
     *
     * @return string
     */
    public function getSuggestedCategoriesJson($namePart)
    {
        $storeId = $this->getVendor()->getStoreId();

        /* @var $collection Collection */
        $collection = $this->_categoryFactory->create()->getCollection();

        $matchingNamesCollection = clone $collection;
        $escapedNamePart = $this->_resourceHelper->addLikeEscape(
            $namePart,
            ['position' => 'any']
        );
        $matchingNamesCollection->addAttributeToFilter(
            'name',
            ['like' => $escapedNamePart]
        )->addFieldToFilter(
            'category_id',
            ['neq' => $this->getRootId()]
        )->addFieldToFilter('vendor_id', $this->getVendor()->getId());

        $shownCategoriesIds = [];
        foreach ($matchingNamesCollection as $category) {
            foreach (explode('/', $category->getPath()) as $parentId) {
                $shownCategoriesIds[$parentId] = 1;
            }
        }

        $collection->addAttributeToFilter(
            'category_id',
            ['in' => array_keys($shownCategoriesIds)]
        )->addFieldToFilter('vendor_id', $this->getVendor()->getId());

        $categoryById = [
            $this->getVendor()->getId() => [
                'id' => $this->getVendor()->getId(),
                'children' => [],
            ],
        ];
        foreach ($collection as $category) {
            foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                if (!isset($categoryById[$categoryId])) {
                    $categoryById[$categoryId] = ['id' => $categoryId, 'children' => []];
                }
            }
            $categoryById[$category->getId()]['is_active'] = $category->getIsActive();
            $categoryById[$category->getId()]['label'] = $category->getName();
            $categoryById[$category->getParentId()]['children'][] = &$categoryById[$category->getId()];
        }

        return $this->_jsonEncoder->encode($categoryById[$this->getVendor()->getId()]['children']);
    }

    /**
     * @return string
     */
    public function getAddRootButtonHtml()
    {
        return $this->getChildHtml('add_root_button');
    }

    /**
     * @return string
     */
    public function getAddSubButtonHtml()
    {
        return $this->getChildHtml('add_sub_button');
    }

    /**
     * @return string
     */
    public function getExpandButtonHtml()
    {
        return $this->getChildHtml('expand_button');
    }

    /**
     * @return string
     */
    public function getCollapseButtonHtml()
    {
        return $this->getChildHtml('collapse_button');
    }

    /**
     * @param bool|null $expanded
     *
     * @return string
     */
    public function getLoadTreeUrl($expanded = null)
    {
        $params = ['_current' => true, 'id' => null, 'store' => null];
        if (is_null($expanded) && $this->vendorSession->getIsTreeWasExpanded() || $expanded == true) {
            $params['expand_all'] = true;
        }

        return $this->getUrl('*/*/categoriesJson', $params);
    }

    /**
     * @return string
     */
    public function getNodesUrl()
    {
        return $this->getUrl('catalog/category/jsonTree');
    }

    /**
     * @return string
     */
    public function getSwitchTreeUrl()
    {
        return $this->getUrl(
            'catalog/category/tree',
            ['_current' => true, 'store' => null, '_query' => false, 'id' => null, 'parent' => null]
        );
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsWasExpanded()
    {
        return $this->vendorSession->getIsTreeWasExpanded();
    }

    /**
     * @return string
     */
    public function getMoveUrl()
    {
        return $this->getUrl('catalog/category/move');
    }

    /**
     * @param mixed|null $parenNodeCategory
     *
     * @return array
     */
    public function getTree($parenNodeCategory = null)
    {
        $rootArray = $this->_getNodeJson($this->getRoot($parenNodeCategory));
        $tree = isset($rootArray['children']) ? $rootArray['children'] : [];

        return $tree;
    }

    /**
     * @param mixed|null $parenNodeCategory
     *
     * @return string
     */
    public function getTreeJson($parenNodeCategory = null)
    {
        $rootArray = $this->_getNodeJson($this->getRoot($parenNodeCategory));
        $json = $this->_jsonEncoder->encode(isset($rootArray['children']) ? $rootArray['children'] : []);

        return $json;
    }

    /**
     * Get JSON of array of categories, that are breadcrumbs for specified category path.
     *
     * @param string $path
     * @param string $javascriptVarName
     *
     * @return string
     */
    public function getBreadcrumbsJavascript($path, $javascriptVarName)
    {
        if (empty($path)) {
            return '';
        }

        $categories = $this->_categoryTree->loadBreadcrumbsArray($path);
        if (empty($categories)) {
            return '';
        }
        foreach ($categories as $key => $category) {
            $categories[$key] = $this->_getNodeJson($category);
        }

        return '<script>require(["prototype"], function(){'.$javascriptVarName.' = '.$this->_jsonEncoder->encode(
            $categories
        ).
        ';'.
        ($this->canAddSubCategory() ? '$("add_subcategory_button").show();' : '$("add_subcategory_button").hide();').
        '});</script>';
    }

    /**
     * Get JSON of a tree node or an associative array.
     *
     * @param Node|array $node
     * @param int        $level
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getNodeJson($node, $level = 0)
    {
        // create a node from data array
        if (is_array($node)) {
            $node = new Node($node, 'category_id', new \Magento\Framework\Data\Tree());
        }

        $item = [];
        $item['text'] = $this->buildNodeName($node);

       // $rootForStores = in_array($node->getEntityId(), $this->getRootIds());

        $item['id'] = $node->getId();
       // $item['store'] = (int)$this->getStore()->getId();
        $item['path'] = $node->getData('path');

        $item['cls'] = 'folder '.($node->getIsActive() ? 'active-category' : 'no-active-category');
        //$item['allowDrop'] = ($level<3) ? true : false;
        $allowMove = $this->_isCategoryMoveable($node);
        $item['allowDrop'] = $allowMove;
        // disallow drag if it's first level and category is root of a store (root has level 0)
        $item['allowDrag'] = $allowMove && ($node->getLevel() == 0 ? false : true);

        if ((int) $node->getChildrenCount() > 0) {
            $item['children'] = [];
        }

        $isParent = $this->_isParentSelectedCategory($node);

        if ($node->hasChildren()) {
            $item['children'] = [];
            if (!($this->getUseAjax() && $node->getLevel() > 0 && !$isParent)) {
                foreach ($node->getChildren() as $child) {
                    $item['children'][] = $this->_getNodeJson($child, $level + 1);
                }
            }
        }

        if ($isParent || $node->getLevel() < 2) {
            $item['expanded'] = true;
        }

        return $item;
    }

    /**
     * Get category name.
     *
     * @param \Magento\Framework\DataObject $node
     *
     * @return string
     */
    public function buildNodeName($node)
    {
        $result = $this->escapeHtml($node->getName());
        if ($this->_withProductCount) {
            $result .= ' ('.$node->getProductCount().')';
        }

        return $result;
    }

    /**
     * @param Node|array $node
     *
     * @return bool
     */
    protected function _isCategoryMoveable($node)
    {
        $options = new \Magento\Framework\DataObject(['is_moveable' => true, 'category' => $node]);

        return $options->getIsMoveable();
    }

    /**
     * check parent selected category.
     *
     * @param Node|array $node
     *
     * @return bool
     */
    protected function _isParentSelectedCategory($node)
    {
        if ($node && $this->getCategory()) {
            $pathIds = $this->getCategory()->getPathIds();
            if (in_array($node->getId(), $pathIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if page loaded by outside link to category edit.
     *
     * @return bool
     */
    public function isClearEdit()
    {
        return (bool) $this->getRequest()->getParam('clear');
    }

    /**
     * Check availability of adding root category.
     *
     * @return bool
     */
    public function canAddRootCategory()
    {
        //return always true so far
        $options = new \Magento\Framework\DataObject(['is_allow' => true]);

        return $options->getIsAllow();
    }

    /**
     * Check availability of adding sub category.
     *
     * @return bool
     */
    public function canAddSubCategory()
    {
        //return always true so far
        $options = new \Magento\Framework\DataObject(['is_allow' => true]);

        return $options->getIsAllow();
    }
}
