<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\Rewrite;

use Vnecoms\VendorsCategory\Model\Category;

class ChildrenCategoriesProvider
{
    /** @var array */
    protected $childrenIds = [];

    /**
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     * @param bool                                    $recursive
     *
     * @return \Vnecoms\VendorsCategory\Model\Category[]
     */
    public function getChildren(Category $category, $recursive = false)
    {
        return $category->isObjectNew() ? [] : $category->getResourceCollection()
            ->addIdFilter($this->getChildrenIds($category, $recursive));
    }

    /**
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     * @param bool                                    $recursive
     *
     * @return int[]
     */
    public function getChildrenIds(Category $category, $recursive = false)
    {
        $cacheKey = $category->getId().'_'.(int) $recursive;
        if (!isset($this->childrenIds[$cacheKey])) {
            $connection = $category->getResource()->getConnection();
            $select = $connection->select()
                ->from($category->getResource()->getMainTable(), 'category_id')
                ->where($connection->quoteIdentifier('path').' LIKE :c_path');
            $bind = ['c_path' => $category->getPath().'/%'];
            if (!$recursive) {
                $select->where($connection->quoteIdentifier('level').' <= :c_level');
                $bind['c_level'] = $category->getLevel() + 1;
            }
            $this->childrenIds[$cacheKey] = $connection->fetchCol($select, $bind);
        }

        return $this->childrenIds[$cacheKey];
    }
}
