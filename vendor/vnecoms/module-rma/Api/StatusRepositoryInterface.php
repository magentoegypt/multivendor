<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * CMS block CRUD interface.
 * @api
 */
interface StatusRepositoryInterface
{
    /**
     * Save status.
     *
     * @param \Vnecoms\RMA\Api\Data\StatusInterface $template
     * @return \Vnecoms\RMA\Api\Data\StatusInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\StatusInterface $status);

    /**
     * Retrieve Status.
     *
     * @param int $statusId
     * @return \Vnecoms\RMA\Api\Data\StatusInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($statusId);

    /**
     * Retrieve status matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\RMA\Api\Data\StatusSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete status.
     *
     * @param \Vnecoms\RMA\Api\Data\StatusInterface $status
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\StatusInterface $status);

    /**
     * Delete Status by ID.
     *
     * @param int $statusId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($statusId);
}
