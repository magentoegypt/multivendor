<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Api;

/**
 * Interface PageRepositoryInterface
 * @package Vnecoms\VendorsCms\Api
 */
interface PageRepositoryInterface
{
    /**
     * Save page.
     * @param int $customerId
     * @param \Vnecoms\VendorsCms\Api\Data\PageInterface $page
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save($customerId, \Vnecoms\VendorsCms\Api\Data\PageInterface $page);

    /**
     * Retrieve page.
     *
     * @param int $pageId
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($pageId);

    /**
     * Retrieve pages matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageSearchResultsInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete page.
     *
     * @param \Vnecoms\VendorsCms\Api\Data\PageInterface $page
     *
     * @return bool true on success
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Vnecoms\VendorsCms\Api\Data\PageInterface $page);

    /**
     * Delete page by ID.
     * @param int $customerId
     * @param int $pageId
     *
     * @return bool true on success
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($customerId, $pageId);
}
