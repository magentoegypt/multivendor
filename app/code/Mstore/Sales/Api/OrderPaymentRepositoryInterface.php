<?php

namespace Mstore\Sales\Api;

interface OrderPaymentRepositoryInterface
{
    /**
     * Lists order payments that match specified search criteria.
     *
     * @param int $customerId customer id
     * @param int $orderId order id
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria The search criteria.
     * @return \Magento\Sales\Api\Data\OrderPaymentSearchResultInterface Order payment search result interface.
     */
    public function getList($customerId, $orderId, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
