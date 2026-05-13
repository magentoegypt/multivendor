<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Block\Vendors\Category;

use Magento\Framework\Data\Tree\Node;

/**
 * Class AbstractCategory.
 */
class AbstractCategory extends \Magento\Backend\Block\Template
{
    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Tree
     */
    protected $_categoryTree;

    /**
     * @var \Vnecoms\VendorsCategory\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var bool
     */
    protected $_withProductCount;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Tree $categoryTree,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsCategory\Model\CategoryFactory $categoryFactory,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        array $data = []
    ) {
        $this->_categoryTree = $categoryTree;
        $this->_coreRegistry = $registry;
        $this->_categoryFactory = $categoryFactory;
        $this->_withProductCount = true;
        $this->vendorSession = $vendorSession;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current category instance.
     *
     * @return array|null
     */
    public function getCategory()
    {
        return $this->_coreRegistry->registry('category');
    }

    /**
     * Get Category ID
     * if can not get id, return root category id.
     *
     * @return int|string|null
     */
    public function getCategoryId()
    {
        if ($this->getCategory()) {
            return $this->getCategory()->getId();
        }

        return $this->getRootId();
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->getCategory()->getName();
    }

    /**
     * return 0 if return category not existed.
     *
     * @return int|string
     */
    public function getCategoryPath()
    {
        if ($this->getCategory()) {
            return $this->getCategory()->getPath();
        }

        return $this->getRootId();
    }

    /**
     * @param mixed|null $parentNodeCategory
     * @param int        $recursionLevel
     *
     * @return Node|array|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getRoot($parentNodeCategory = null, $recursionLevel = 3)
    {
        if ($parentNodeCategory !== null && $parentNodeCategory->getId()) {
            return $this->getNode($parentNodeCategory, $recursionLevel);
        }
        $root = $this->_coreRegistry->registry('root');
        if ($root === null) {
            $rootId = $this->getRootId();

            $tree = $this->_categoryTree->load(null, $recursionLevel);

            if ($this->getCategory()) {
                $tree->loadEnsuredNodes($this->getCategory(), $tree->getNodeById($rootId));
            }

            $tree->addCollectionData($this->getCategoryCollection());

            $root = $tree->getNodeById($rootId);

            if ($root && $rootId != $this->getRootId()) {
                $root->setIsVisible(true);
            } elseif ($root && $root->getId() == $this->getRootId()) {
                $root->setName(__('Root'));
            }

            $this->_coreRegistry->register('root', $root);
        }

        return $root;
    }

    /**
     * get category collection filter by vendor id.
     *
     * @param $withActive boolean
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getCategoryCollection($withActive = false)
    {
        $collection = $this->getData('category_collection');
        if ($collection === null) {
            $collection = $this->_categoryFactory->create()->getCollection()->setLoadProductCount($this->_withProductCount);

            $collection->addFieldToFilter('vendor_id', $this->getVendor()->getId());
            if ($withActive) {
                $collection->addFieldToFilter('is_active', 1);
            }
            $this->setData('category_collection', $collection);
        }

        return $collection;
    }

    /**
     * @param mixed $parentNodeCategory
     * @param int   $recursionLevel
     *
     * @return Node
     */
    public function getNode($parentNodeCategory, $recursionLevel = 2)
    {
        $nodeId = $parentNodeCategory->getId();
        $node = $this->_categoryTree->loadNode($nodeId);
        $node->loadChildren($recursionLevel);

        if ($node && $nodeId != $this->getRootId()) {
            $node->setIsVisible(true);
        } elseif ($node && $node->getId() == $this->getRootId()) {
            $node->setName(__('Root'));
        }

        $this->_categoryTree->addCollectionData($this->getCategoryCollection());

        return $node;
    }

    /**
     * @param array $args
     *
     * @return string
     */
    public function getSaveUrl(array $args = [])
    {
        $params = ['_current' => false, '_query' => false];
        $params = array_merge($params, $args);

        return $this->getUrl('catalog/category/save', $params);
    }

    /**
     * @return string
     */
    public function getEditUrl()
    {
        return $this->getUrl(
            'catalog/category/edit',
            ['store' => null, '_query' => false, 'id' => null, 'parent' => null]
        );
    }

    /**
     * @return Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->vendorSession->getVendor();
    }

    /**
     * root category id.
     *
     * @return int
     */
    public function getRootId()
    {
        return $this->_categoryFactory->create()->getRootId($this->getVendor()->getId());
    }
}
