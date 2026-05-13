<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Request CRUD interface.
 * @api
 */
interface RequestRepositoryInterface
{
    /**
     * Save Request.
     *
     * @param \Vnecoms\RMA\Api\Data\RequestInterface $request
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\RequestInterface $request);

    /**
     * Retrieve Request.
     *
     * @param int $templateId
     * @return \Vnecoms\RMA\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($requestId);

    /**
     * Retrieve requests matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\HelpDesk\Api\Data\TicketSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete Request.
     *
     * @param \Vnecoms\RMA\Api\Data\RequestInterface $request
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\RequestInterface $request);

    /**
     * Delete Request by ID.
     *
     * @param int $requestId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($requestId);
}
