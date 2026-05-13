<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Api;

/**
 * @api
 */
interface CategoryLinkRepositoryInterface
{
    /**
     * Assign a product to the required category.
     *
     * @param \Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface $productLink
     *
     * @return bool will returned True if assigned
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function save(\Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface $productLink);

    /**
     * Remove the product assignment from the category.
     *
     * @param \Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface $productLink
     *
     * @return bool will returned True if products successfully deleted
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function delete(\Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface $productLink);

    /**
     * Remove the product assignment from the category by category id and sku.
     *
     * @param string $categoryId
     * @param string $sku
     *
     * @return bool will returned True if products successfully deleted
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function deleteByIds($categoryId, $sku);
}
