<?php

namespace Mstore\Sales\Api;

interface OrderAddressRepositoryInterface
{
    /**
     * Lists order addresses that match specified search criteria.
     *
     * @param int $customerId the customer id
     * @param int $orderId the order id
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria The search criteria.
     * @return \Magento\Sales\Api\Data\OrderAddressSearchResultInterface Order address search result interface.
     */
    public function getList($customerId, $orderId, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
