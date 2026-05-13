<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Api;

/**
 * @api
 */
interface CategoryManagementInterface
{
    /**
     * Retrieve list of categories.
     * @param int $customerId
     * @param int $rootCategoryId
     * @param int $depth
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException If ID is not found
     *
     * @return \Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterface containing Tree objects
     */
    public function getTree($customerId, $rootCategoryId = null, $depth = null);

    /**
     * Move category.
     *
     * @param int $categoryId
     * @param int $parentId
     * @param int $afterId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function move($categoryId, $parentId, $afterId = null);

    /**
     * Provide the number of category count.
     *
     * @return int
     */
    public function getCount();
}
