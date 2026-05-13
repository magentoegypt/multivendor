<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Api;

/**
 * Interface BlockRepositoryInterface
 * @package Vnecoms\VendorsCms\Api
 */
interface BlockRepositoryInterface
{
    /**
     * Save block.
     * @param int $customerId
     * @param \Vnecoms\VendorsCms\Api\Data\BlockInterface $block
     *
     * @return \Vnecoms\VendorsCms\Api\Data\BlockInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save($customerId, Data\BlockInterface $block);

    /**
     * Retrieve block.
     *
     * @param int $blockId
     *
     * @return \Vnecoms\VendorsCms\Api\Data\BlockInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($blockId);

    /**
     * Retrieve blocks matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Vnecoms\VendorsCms\Api\Data\BlockSearchResultsInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete block.
     *
     * @param \Vnecoms\VendorsCms\Api\Data\BlockInterface $block
     *
     * @return bool true on success
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\BlockInterface $block);

    /**
     * Delete block by ID.
     * @param int $customerId
     * @param int $blockId
     *
     * @return bool true on success
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($customerId, $blockId);
}
