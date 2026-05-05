<?php

namespace Mstore\Sales\Api;

/**
 * Interface OrderRepositoryInterface
 * @package Mstore\Sales\Api
 * @api
 */
interface OrderRepositoryInterface
{
    /**
     * Loads a specified order.
     *
     * @param int $customerId
     * @param int $id
     * @return \Magento\Sales\Api\Data\OrderInterface Order interface.
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($customerId, $id);

    /**
     * @param int $customerId
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     */
    public function getList($customerId, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
