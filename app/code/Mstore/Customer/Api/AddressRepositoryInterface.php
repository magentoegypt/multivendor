<?php

namespace Mstore\Customer\Api;

/**
 * Interface AddressRepositoryInterface
 * @package Mstore\Customer\Api
 * @api
 */
interface AddressRepositoryInterface
{
    /**
     * Update address for the given customerId.
     *
     * @param int $customerId
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return \Magento\Customer\Api\Data\AddressInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function save($customerId, $address);

    /**
     * Delete address by the given $addressId.
     *
     * @param int $customerId
     * @param int $addressId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function deleteById($customerId, $addressId);

    /**
     * Retrieve address by $addressId.
     *
     * @param int $customerId
     * @param int $addressId
     * @return \Magento\Customer\Api\Data\AddressInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($customerId, $addressId);

    /**
     * Retrieve address list
     *
     * @param int $customerId
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Customer\Api\Data\AddressSearchResultsInterface
     */
    public function getList($customerId, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
