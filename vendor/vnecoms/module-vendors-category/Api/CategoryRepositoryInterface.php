<?php

namespace Vnecoms\VendorsCategory\Api;

interface CategoryRepositoryInterface
{
    /**
     * Create category service.
     *
     * @param \Vnecoms\VendorsCategory\Api\Data\CategoryInterface $category
     *
     * @return \Vnecoms\VendorsCategory\Api\Data\CategoryInterface
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Vnecoms\VendorsCategory\Api\Data\CategoryInterface $category);

    /**
     * Get info about category by category id.
     *
     * @param int $categoryId
     *
     * @return \Vnecoms\VendorsCategory\Api\Data\CategoryInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($categoryId);

    /**
     * Delete category by identifier.
     *
     * @param \Vnecoms\VendorsCategory\Api\Data\CategoryInterface $category category which will deleted
     *
     * @return bool Will returned True if deleted
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function delete(\Vnecoms\VendorsCategory\Api\Data\CategoryInterface $category);

    /**
     * Delete category by identifier.
     *
     * @param int $categoryId
     *
     * @return bool Will returned True if deleted
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteByIdentifier($categoryId);
}
