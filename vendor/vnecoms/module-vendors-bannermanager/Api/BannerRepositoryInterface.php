<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Banner CRUD interface
 * @api
 */
interface BannerRepositoryInterface
{
    /**
     * Save banner
     *
     * @param \Vnecoms\BannerManager\Api\Data\BannerInterface $banner
     * @return \Vnecoms\BannerManager\Api\Data\BannerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Vnecoms\BannerManager\Api\Data\BannerInterface $banner);

    /**
     * Retrieve banner
     *
     * @param int $bannerId
     * @return \Vnecoms\BannerManager\Api\Data\BannerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($bannerId);

    /**
     * Retrieve banners matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\BannerManager\Api\Data\BannerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete banner
     *
     * @param \Vnecoms\BannerManager\Api\Data\BannerInterface $banner
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Vnecoms\BannerManager\Api\Data\BannerInterface $banner);

    /**
     * Delete banner by ID
     *
     * @param int $bannerId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($bannerId);
}
