<?php
/**
 * *
 *  * Category.php
 *  *
 *  * @file        Category.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

/**
 * *
 *  * Category.php
 *  *
 *  * @file        Category.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

namespace Vnecoms\VendorsCategory\Model\ResourceModel;

class Category extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var Category\CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @var Category\TreeFactory
     */
    protected $_categoryTreeFactory;

    /**
     * @var string
     */
    protected $_categoryProductTable;

    /**
     * @var \Magento\Framework\Data\Tree\Db
     */
    protected $_tree;

    /**
     * Core event manager proxy.
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager = null;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    protected $productCollectionFactory;

    protected function _construct()
    {
        $this->_init('ves_vendorscategory_category', 'category_id');
    }

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\TreeFactory $treeFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_eventManager = $eventManager;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_categoryTreeFactory = $treeFactory;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Category product table name getter.
     *
     * @return string
     */
    public function getCategoryProductTable()
    {
        if (!$this->_categoryProductTable) {
            $this->_categoryProductTable = $this->getTable('ves_vendorscategory_category_product');
        }

        return $this->_categoryProductTable;
    }

    /**
     * Get positions of associated to category products.
     *
     * @param \Magento\Catalog\Model\Category $category
     *
     * @return array
     */
    public function getProductsPosition($category)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('ves_vendorscategory_category_product'),
            ['product_id', 'position']
        )->where(
            'category_id = :category_id'
        );
        $bind = ['category_id' => (int) $category->getId()];

        return $this->getConnection()->fetchPairs($select, $bind);
    }

    /**
     * Get children categories.
     *
     * @param $category
     *
     * @return \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection
     */
    public function getChildrenCategories($category)
    {
        $collection = $category->getCollection();
        /* @var $collection \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection */
        $collection->addIdFilter(
            $category->getChildren()
        )->setOrder(
            'position',
            \Magento\Framework\DB\Select::SQL_ASC
        )->joinUrlRewrite()->load();

        return $collection;
    }

    /**
     * Return parent categories of category.
     *
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     *
     * @return \Magento\Framework\DataObject[]
     */
    public function getParentCategories($category)
    {
        $pathIds = array_reverse(explode(',', $category->getPathWithOutRoot($this->getVendor()->getId())));
        /** @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $categories */
        $categories = $this->_categoryCollectionFactory->create();

        return $categories->addFieldToFilter(
            'category_id',
            ['in' => $pathIds]
        )->addFieldToFilter(
            'is_active',
            1
        )->load()->getItems();
    }

    /**
     * Retrieve categories.
     *
     * @param int         $parent
     * @param int         $recursionLevel
     * @param bool|string $sorted
     * @param bool        $asCollection
     * @param bool        $toLoad
     *
     * @return \Magento\Framework\Data\Tree\Node\Collection|\Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    public function getCategories($parent, $recursionLevel = 0, $sorted = false, $asCollection = false, $toLoad = true)
    {
        $tree = $this->_categoryTreeFactory->create();
        /* @var $tree \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Tree */
        $nodes = $tree->loadNode($parent)->loadChildren($recursionLevel)->getChildren();

        $tree->addCollectionData(null, $sorted, $parent, $toLoad, true);

        if ($asCollection) {
            return $tree->getCollection();
        }

        return $nodes;
    }

    /**
     * validate object
     * (non-PHPdoc)
     */
    public function validate($store)
    {
        $table = $this->getTable('ves_vendorscategory_category');
        $connection = $this->getConnection();
        $select = $connection->select();

        $select->from(
            $table
        )->where(
            'url_key = :url_key'
        )->where(
            'vendor_id = :vendor_id'
        );

        $bind = [
            'url_key' => $store->getUrlKey(),
            'vendor_id' => $store->getVendorId()
        ];
        $existRowId = $connection->fetchOne($select,$bind);

        if ($existRowId && ($store->getId() != $existRowId)) {
            return [__("The value specified in the URL Key field would generate a URL that already exists.To resolve this conflict, you can either change the value of the URL Key field (located in the Search Engine Optimization section) to a unique value, or change the Request Path fields")];
        }

        return [];
    }

    /**
     * check url key exist
     * @param $vendorId
     * @param $urlKey
     * @return bool
     */
    public function loadByUrlKeyAndVendorId($vendorId , $urlKey){
        $table = $this->getTable('ves_vendorscategory_category');
        $connection = $this->getConnection();
        $select = $connection->select();

        $select->from(
            $table
        )->where(
            'url_key = :url_key'
        )->where(
            'vendor_id = :vendor_id'
        );

        $bind = [
            'url_key' => $urlKey,
            'vendor_id' => $vendorId
        ];

        $existRowId = $connection->fetchOne($select,$bind);

        if ($existRowId) {
            return true;
        }
        return false;
    }
    /**
     * Get count of active/not active children categories.
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param bool                            $isActiveFlag
     *
     * @return int
     */
    public function getChildrenAmount($category, $isActiveFlag = true)
    {
    }

    /**
     * Return children ids of category.
     *
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     * @param bool                                    $recursive
     *
     * @return array
     */
    public function getChildren($category, $recursive = true)
    {
        $connection = $this->getConnection();
        $bind = [
            'c_path' => $category->getPath().'/%',
        ];
        $select = $this->getConnection()->select()->from(
            ['m' => $this->getMainTable()],
            'category_id'
        )->where(
            $connection->quoteIdentifier('path').' LIKE :c_path'
        );
        if (!$recursive) {
            $select->where($connection->quoteIdentifier('level').' <= :c_level');
            $bind['c_level'] = $category->getLevel() + 1;
        }

        return $connection->fetchCol($select, $bind);
    }

    /**
     * Check if category id exist.
     *
     * @param int $entityId
     *
     * @return bool
     */
    public function checkId($entityId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            'category_id'
        )->where(
            'category_id = :category_id'
        );
        $bind = ['category_id' => $entityId];

        return $this->getConnection()->fetchOne($select, $bind);
    }

    /**
     * Return all children ids of category (with category id).
     *
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     *
     * @return array
     */
    public function getAllChildren($category)
    {
        $children = $this->getChildren($category);
        $myId = [$category->getId()];
        $children = array_merge($myId, $children);

        return $children;
    }

    /**
     * get category product from table `ves_vendorscategory_category_product`.
     *
     * @param $category
     * @param $isApproved bool
     * @param  $statusFilter bool
     * @param  $visibilityFilter bool
     *
     * @return int
     */
    public function getProductCount($category, $isApproved = false, $statusFilter = false, $visibilityFilter = false)
    {
        $productTable = $this->getTable('ves_vendorscategory_category_product');

        $select = $this->getConnection()->select()->from(
            ['main_table' => $productTable],
            ['product_id']
        )->where(
            'main_table.category_id = :category_id'
        );

        $bind = ['category_id' => (int) $category->getId()];
        $productIds = $this->getConnection()->fetchCol($select, $bind);

      //  var_dump($productIds);die();
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addIdFilter($productIds);
        if ($isApproved) {
            $productCollection->addAttributeToFilter('approval', \Vnecoms\VendorsProduct\Model\Source\Approval::STATUS_APPROVED);
        }
        if ($statusFilter) {
            $productCollection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        }
        if ($visibilityFilter) {
            $productCollection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
        }
        $productCollection->load();
        return intval($productCollection->count());
    }

    /**
     * Get children categories count.
     *
     * @param int $categoryId
     *
     * @return int
     */
    public function getChildrenCount($categoryId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            'children_count'
        )->where(
            'category_id = :category_id'
        );
        $bind = ['category_id' => $categoryId];

        return $this->getConnection()->fetchOne($select, $bind);
    }

    /**
     * Process category data before saving
     * prepare path and increment children count for parent categories.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_beforeSave($object);

        if (!$object->getChildrenCount()) {
            $object->setChildrenCount(0);
        }
        //set vendor id
        if (!$object->getVendorId()) {
            $object->setVendorId($this->getVendor()->getId());
        }

        if (!$object->getRealCategoryId()) {
            $object->setData('real_category_id', $object->getId());
        }

        if ($object->isObjectNew()) {
            //if Root, must not do this
            if ($object->getId() == $this->getRootId($object->getVendorId())) {
                return $this;
            }
            if ($object->getPosition() === null) {
                $object->setPosition($this->_getMaxPosition($object->getPath()) + 1);
            }
            $path = explode('/', $object->getPath());
            $level = count($path)  - ($object->getId() ? 1 : 0);
            $toUpdateChild = array_diff($path, [$object->getId()]);

            if (!$object->hasPosition()) {
                $object->setPosition($this->_getMaxPosition(implode('/', $toUpdateChild)) + 1);
            }
            if (!$object->hasLevel()) {
                $object->setLevel($level);
            }
            if (!$object->hasParentId() && $level) {
                $object->setParentId($path[$level - 1]);
            }
            if (!$object->getId()) {
                $object->setPath($object->getPath().'/');
            }


            $this->getConnection()->update(
                $this->getMainTable(),
                ['children_count' => new \Zend_Db_Expr('children_count+1')],
                ['category_id IN(?)' => $toUpdateChild]
            );
        }
       // var_dump($object->getData());die();

        return $this;
    }

    /**
     * Process category data after save category object
     * save related products ids and update path value.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getId() == 0) {
            return parent::_afterSave($object);
        }
        /*
         * Add identifier for new category
         */
        if (substr($object->getPath(), -1) == '/') {
            $object->setPath($object->getPath().$object->getId());
            $this->_savePath($object);
        }

        $object->setData('real_category_id', $object->getId());
        $this->_saveRealCategoryId($object);

        $this->_saveCategoryProducts($object);

        return parent::_afterSave($object);
    }

    protected function _afterDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->_deleteCategoryProduct($object);

        return parent::_afterDelete($object); // TODO: Change the autogenerated stub
    }

    /**
     * Delete category product table when delete category.
     *
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     */
    protected function _deleteCategoryProduct($category)
    {
        $connection = $this->getConnection();
        /*
         * Delete products from category
         */

        $cond = ['category_id=?' => $category->getId()];
        $connection->delete($this->getCategoryProductTable(), $cond);
    }

    /**
     * update real category id.
     *
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     *
     * @return $this
     */
    protected function _saveRealCategoryId($category)
    {
        if ($category->getId()) {
            $this->getConnection()->update(
                $this->getMainTable(),
                ['real_category_id' => $category->getData('real_category_id')],
                ['category_id = ?' => $category->getId()]
            );
          //  $category->unsetData('path_ids');
        }

        return $this;
    }

    /**
     * Update path field.
     *
     * @param \Vnecoms\VendorsCategory\Model\Category $object
     *
     * @return $this
     */
    protected function _savePath($object)
    {
        if ($object->getId()) {
            $this->getConnection()->update(
                $this->getMainTable(),
                ['path' => $object->getPath()],
                ['category_id = ?' => $object->getId()]
            );
            $object->unsetData('path_ids');
        }

        return $this;
    }

    /**
     * Save category products relation.
     *
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _saveCategoryProducts($category)
    {
        $category->setIsChangedProductList(false);
        $id = $category->getId();
        /*
         * new category-product relationships
         */
        $products = $category->getPostedProducts();

        /*
         * Example re-save category
         */
        if ($products === null) {
            return $this;
        }

        /*
         * old category-product relationships
         */
        $oldProducts = $category->getProductsPosition();

        $insert = array_diff_key($products, $oldProducts);
        $delete = array_diff_key($oldProducts, $products);

        /*
         * Find product ids which are presented in both arrays
         * and saved before (check $oldProducts array)
         */
        $update = array_intersect_key($products, $oldProducts);
        $update = array_diff_assoc($update, $oldProducts);

        $connection = $this->getConnection();

        /*
         * Delete products from category
         */
        if (!empty($delete)) {
            $cond = ['product_id IN(?)' => array_keys($delete), 'category_id=?' => $id];
            $connection->delete($this->getCategoryProductTable(), $cond);
        }

        /*
         * Add products to category
         */
        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $productId => $position) {
                $data[] = [
                    'category_id' => (int) $id,
                    'product_id' => (int) $productId,
                    'position' => (int) $position,
                ];
            }
            $connection->insertMultiple($this->getCategoryProductTable(), $data);
        }

        /*
         * Update product positions in category
         */
        if (!empty($update)) {
            foreach ($update as $productId => $position) {
                $where = ['category_id = ?' => (int) $id, 'product_id = ?' => (int) $productId];
                $bind = ['position' => (int) $position];
                $connection->update($this->getCategoryProductTable(), $bind, $where);
            }
        }

        if (!empty($insert) || !empty($delete)) {
            $productIds = array_unique(array_merge(array_keys($insert), array_keys($delete)));
            $this->_eventManager->dispatch(
                'vendor_catalog_category_change_products',
                ['category' => $category, 'product_ids' => $productIds]
            );
        }

        if (!empty($insert) || !empty($update) || !empty($delete)) {
            $category->setIsChangedProductList(true);

            /*
             * Setting affected products to category for third party engine index refresh
             */
            $productIds = array_keys($insert + $delete + $update);
            $category->setAffectedProductIds($productIds);
        }

        return $this;
    }

    /**
     * Retrieve category tree object.
     *
     * @return \Magento\Framework\Data\Tree\Db
     */
    protected function _getTree()
    {
        if (!$this->_tree) {
            $this->_tree = $this->_categoryTreeFactory->create()->load();
        }

        return $this->_tree;
    }

    /**
     * Get total number of persistent categories in the system, excluding the default category.
     *
     * @return int
     */
    public function countVisible()
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from($this->getMainTable(), 'COUNT(*)')->where('parent_id != ?', 0);

        return (int) $connection->fetchOne($select);
    }

    /**
     * Get maximum position of child categories by specific tree path.
     *
     * @param string $path
     *
     * @return int
     */
    protected function _getMaxPosition($path)
    {
        $connection = $this->getConnection();
        $positionField = $connection->quoteIdentifier('position');
        $level = count(explode('/', $path));
        $bind = ['c_level' => $level, 'c_path' => $path.'/%'];
        $select = $connection->select()->from(
            $this->getTable('ves_vendorscategory_category'),
            'MAX('.$positionField.')'
        )->where(
            $connection->quoteIdentifier('path').' LIKE :c_path'
        )->where(
            $connection->quoteIdentifier('level').' = :c_level'
        );

        $position = $connection->fetchOne($select, $bind);
        if (!$position) {
            $position = 0;
        }

        return $position;
    }

    /**
     * Get Root Category ID of vendor.
     *
     * @param $vendorId
     *
     * @return int
     */
    public function getRootId($vendorId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable()
        )->where(
            'vendor_id = :vendor_id'
        )->where(
            'level = :level'
        );
        $bind = ['vendor_id' => $vendorId, 'level' => 0];

        if ($result = $this->getConnection()->fetchOne($select, $bind)) {
            return $result;
        } else {
            $this->getConnection()->insert($this->getMainTable(), [
                'real_category_id' => $vendorId,
                'name' => __('Root'),
               // 'path' => $vendorId,
                'is_active' => 1,
                'level' => 0,
                'vendor_id' => $vendorId,
                'parent_id' => 0,
                'status' => 1,
                'position' => $vendorId,
                'is_hide_product' => 0,
            ]);
            $this->getConnection()->update($this->getMainTable(), [
                'path' => $this->getConnection()->fetchOne($select, $bind),
            ], ['vendor_id = ?'=> $vendorId, 'level = ?' => 0]);
        }

        if ($result = $this->getConnection()->fetchOne($select, $bind)) {
            return $result;
        }
    }

    /**
     * Get Vendor.
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->vendorSession) {
            return $this->vendorSession->getVendor();
        } else {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\Vendors\Model\Session');

            return $this->vendorSession->getVendor();
        }
    }

    /**
     * Process positions of old parent category children and new parent category children.
     * Get position for moved category.
     *
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     * @param \Vnecoms\VendorsCategory\Model\Category $newParent
     * @param null|int                                $afterCategoryId
     *
     * @return int
     */
    protected function _processPositions($category, $newParent, $afterCategoryId)
    {
        $table = $this->getMainTable();
        $connection = $this->getConnection();
        $positionField = $connection->quoteIdentifier('position');

        $bind = ['position' => new \Zend_Db_Expr($positionField.' - 1')];
        $where = [
            'parent_id = ?' => $category->getParentId(),
            $positionField.' > ?' => $category->getPosition(),
        ];
        $connection->update($table, $bind, $where);

        /*
         * Prepare position value
         */
        if ($afterCategoryId) {
            $select = $connection->select()->from($table, 'position')->where('category_id = :category_id');
            $position = $connection->fetchOne($select, ['category_id' => $afterCategoryId]);
            $position += 1;
        } else {
            $position = 1;
        }

        $bind = ['position' => new \Zend_Db_Expr($positionField.' + 1')];
        $where = ['parent_id = ?' => $newParent->getId(), $positionField.' >= ?' => $position];
        $connection->update($table, $bind, $where);

        return $position;
    }

    /**
     * Move category to another parent node.
     *
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     * @param \Vnecoms\VendorsCategory\Model\Category $newParent
     * @param null|int                                $afterCategoryId
     *
     * @return $this
     */
    public function changeParent(
        \Vnecoms\VendorsCategory\Model\Category $category,
        \Vnecoms\VendorsCategory\Model\Category $newParent,
        $afterCategoryId = null
    ) {
        $childrenCount = $this->getChildrenCount($category->getId()) + 1;
        $table = $this->getMainTable();
        $connection = $this->getConnection();
        $levelFiled = $connection->quoteIdentifier('level');
        $pathField = $connection->quoteIdentifier('path');

        /*
         * Decrease children count for all old category parent categories
         */
        $connection->update(
            $table,
            ['children_count' => new \Zend_Db_Expr('children_count - '.$childrenCount)],
            ['category_id IN(?)' => $category->getParentIds()]
        );

        /*
         * Increase children count for new category parents
         */
        $connection->update(
            $table,
            ['children_count' => new \Zend_Db_Expr('children_count + '.$childrenCount)],
            ['category_id IN(?)' => $newParent->getPathIds()]
        );

        $position = $this->_processPositions($category, $newParent, $afterCategoryId);

        $newPath = sprintf('%s/%s', $newParent->getPath(), $category->getId());
        $newLevel = $newParent->getLevel() + 1;
        $levelDisposition = $newLevel - $category->getLevel();

        /*
         * Update children nodes path
         */
        $connection->update(
            $table,
            [
                'path' => new \Zend_Db_Expr(
                    'REPLACE('.$pathField.','.$connection->quote(
                        $category->getPath().'/'
                    ).', '.$connection->quote(
                        $newPath.'/'
                    ).')'
                ),
                'level' => new \Zend_Db_Expr($levelFiled.' + '.$levelDisposition),
            ],
            [$pathField.' LIKE ?' => $category->getPath().'/%']
        );
        /*
         * Update moved category data
         */
        $data = [
            'path' => $newPath,
            'level' => $newLevel,
            'position' => $position,
            'parent_id' => $newParent->getId(),
        ];
        $connection->update($table, $data, ['category_id = ?' => $category->getId()]);

        // Update category object to new data
        $category->addData($data);
        $category->unsetData('path_ids');

        return $this;
    }

    /**
     * Check category is forbidden to delete.
     * If category is root and assigned to store group return false.
     *
     * @param int $categoryId
     *
     * @return bool
     */
    public function isForbiddenToDelete($categoryId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            ['category_id', 'level']
        )->where(
            'category_id = :category_id'
        )->where(
            'level = 0'
        );
        $result = $this->getConnection()->fetchOne($select, ['category_id' => $categoryId]);

        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * Get category path value by its id.
     *
     * @param int $categoryId
     *
     * @return string
     */
    public function getCategoryPathById($categoryId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            ['path']
        )->where(
            'category_id = :category_id'
        );
        $bind = ['category_id' => (int) $categoryId];

        return $this->getConnection()->fetchOne($select, $bind);
    }

    /**
     * Delete children categories of specific category.
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return $this
     */
    public function deleteChildren(\Magento\Framework\DataObject $object)
    {
        if ($object->getSkipDeleteChildren()) {
            return $this;
        }

        $categories = $this->_categoryCollectionFactory->create();
        $categories->addFieldToFilter('path', ['like' => $object->getPath().'/%']);
        $childrenIds = $categories->getAllIds();
        foreach ($categories as $category) {
            $category->setSkipDeleteChildren(true);
            $category->delete();
        }

        /*
         * Add deleted children ids to object
         * This data can be used in after delete event
         */
        $object->setDeletedChildrenIds($childrenIds);

        return $this;
    }

    /**
     * Process category data before delete
     * update children count for parent category
     * delete child categories.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _beforeDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_beforeDelete($object);
        /*
         * Update children count for all parent categories
         */
        $parentIds = $object->getParentIds();
        if ($parentIds) {
            $childDecrease = $object->getChildrenCount();
            // +1 is itself
            $data = ['children_count' => new \Zend_Db_Expr('children_count - '.$childDecrease)];
            $where = ['category_id IN(?)' => $parentIds];

           // var_dump($data);die();
            $this->getConnection()->update($this->getMainTable(), $data, $where);
        }
        //$this->getAggregateCount()->processDelete($object);
        $this->deleteChildren($object);
    }
}
