<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Item CRUD interface.
 * @api
 */
interface ItemRepositoryInterface
{
    /**
     * Save slide.
     *
     * @param \Vnecoms\BannerManager\Api\Data\ItemInterface $slide
     * @return \Vnecoms\BannerManager\Api\Data\ItemInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Vnecoms\BannerManager\Api\Data\ItemInterface $slide);

    /**
     * Retrieve slide.
     *
     * @param int $slideId
     * @return \Vnecoms\BannerManager\Api\Data\ItemInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($slideId);

    /**
     * Retrieve slides matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\BannerManager\Api\Data\ItemSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete slide.
     *
     * @param \Vnecoms\BannerManager\Api\Data\ItemInterface $slide
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Vnecoms\BannerManager\Api\Data\ItemInterface $slide);

    /**
     * Delete slide by ID.
     *
     * @param int $slideId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($slideId);
}
