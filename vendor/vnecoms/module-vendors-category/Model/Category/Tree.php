<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\Category;

use Magento\Framework\Data\Tree\Node;

/**
 * Retrieve category data represented in tree structure.
 */
class Tree
{
    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Tree
     */
    protected $categoryTree;

    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection
     */
    protected $categoryCollection;

    /**
     * @var \Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterfaceFactory
     */
    protected $treeFactory;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @var
     */
    protected $current_vendor = null;

    /**
     * @param \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Tree       $categoryTree
     * @param \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $categoryCollection
     * @param \Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterfaceFactory   $treeFactory
     */
    public function __construct(
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Tree $categoryTree,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $categoryCollection,
        \Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterfaceFactory $treeFactory
    ) {
        $this->categoryTree = $categoryTree;
        $this->categoryCollection = $categoryCollection;
        $this->treeFactory = $treeFactory;
    }

    /**
     * @param \Vnecoms\VendorsCategory\Model\Category|null $category
     *
     * @return Node|null
     */
    public function getRootNode($category = null)
    {
        if ($category !== null && $category->getId()) {
            return $this->getNode($category);
        }

       // $store = $this->storeManager->getStore();
        $rootId = $this->getRootId();

        $tree = $this->categoryTree->load(null);
        $this->prepareCollection();
        $tree->addCollectionData($this->categoryCollection);
        $root = $tree->getNodeById($rootId);

        return $root;
    }

    /**
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     *
     * @return Node
     */
    protected function getNode(\Vnecoms\VendorsCategory\Model\Category $category)
    {
        $nodeId = $category->getId();
        $node = $this->categoryTree->loadNode($nodeId);
        $node->loadChildren();
        $this->prepareCollection();
        $this->categoryTree->addCollectionData($this->categoryCollection);

        return $node;
    }

    /**
     */
    protected function prepareCollection()
    {
        // $storeId = $this->storeManager->getStore()->getId();
        $this->categoryCollection->addFieldToFilter(
            'vendor_id',
            $this->getVendor()->getId()
        )->setLoadProductCount(
            true
        );
    }

    /**
     * @param \Magento\Framework\Data\Tree\Node $node
     * @param int                               $depth
     * @param int                               $currentLevel
     *
     * @return \Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterface
     */
    public function getTree($node, $depth = null, $currentLevel = 0)
    {
        /** @var \Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterface[] $children */
        $children = $this->getChildren($node, $depth, $currentLevel);
        /** @var \Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterface $tree */
        $tree = $this->treeFactory->create();
        $tree->setId($node->getId())
            ->setParentId($node->getParentId())
            ->setName($node->getName())
            ->setPosition($node->getPosition())
            ->setLevel($node->getLevel())
            ->setIsActive($node->getIsActive())
            ->setProductCount($node->getProductCount())
            ->setChildrenData($children);

        return $tree;
    }

    /**
     * @param \Magento\Framework\Data\Tree\Node $node
     * @param int                               $depth
     * @param int                               $currentLevel
     *
     * @return \Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterface[]|[]
     */
    protected function getChildren($node, $depth, $currentLevel)
    {
        if ($node->hasChildren()) {
            $children = [];
            foreach ($node->getChildren() as $child) {
                if ($depth !== null && $depth <= $currentLevel) {
                    break;
                }
                $children[] = $this->getTree($child, $depth, $currentLevel + 1);
            }

            return $children;
        }

        return [];
    }

    /**
     * @param $vendor
     * @return $this
     */
    public function setVendor($vendor) {
        $this->current_vendor = $vendor;
        return $this;
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if (!$this->current_vendor) {
            if ($this->vendorSession) {
                $vendor =  $this->vendorSession->getVendor();
            } else {
                $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                    ->create('Vnecoms\Vendors\Model\Session');

                $vendor =  $this->vendorSession->getVendor();
            }
            $this->current_vendor = $vendor;
        }
        return $this->current_vendor;
    }

    /**
     * get root ID of each vendor.
     *
     * @return int|string
     */
    public function getRootId()
    {
        $categoryResource = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Vnecoms\VendorsCategory\Model\ResourceModel\Category');
        return $categoryResource->getRootId($this->getVendor()->getId());
    }
}
