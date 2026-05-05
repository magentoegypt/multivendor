<?php

namespace Mstore\Sales\Api;

/**
 * Interface CreditmemoRepositoryInterface
 * @package Mstore\Sales\Api
 * @api
 */
interface CreditmemoRepositoryInterface
{
    /**
     * @param int $customerId
     * @param int $orderId
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Sales\Api\Data\CreditmemoSearchResultInterface Creditmemo search result interface.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList($customerId, $orderId, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
