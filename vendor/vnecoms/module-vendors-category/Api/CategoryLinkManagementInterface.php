<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Api;

/**
 * @api
 */
interface CategoryLinkManagementInterface
{
    /**
     * Get products assigned to category.
     *
     * @param int $categoryId
     *
     * @return \Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface[]
     */
    public function getAssignedProducts($categoryId);

    /**
     * Assign product to given categories.
     *
     * @param string $productSku
     * @param int[]  $categoryIds
     *
     * @return bool
     */
    public function assignProductToCategories($productSku, array $categoryIds);
}
