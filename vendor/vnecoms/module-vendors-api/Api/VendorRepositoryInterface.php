<?php

namespace Vnecoms\VendorsApi\Api;

/**
 * Vendor CRUD interface.
 * @api
 */
interface VendorRepositoryInterface
{
    /**
     * Get customer by Customer ID.
     *
     * @param int $customerId
     * @return \Vnecoms\VendorsApi\Api\Data\VendorInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If customer with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($customerId);

    /**
     * Get customer by Customer ID.
     *
     * @param int $vendorId
     * @return \Vnecoms\VendorsApi\Api\Data\VendorInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If customer with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByVendorId($vendorId);

    /**
     * Create Vendor
     *
     * @param \Vnecoms\VendorsApi\Api\Data\VendorInterfacee $vendor
     * @return \Vnecoms\VendorsApi\Api\Data\VendorInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Vnecoms\VendorsApi\Api\Data\VendorInterface $vendor);

    /**
     * Delete vendor by vendor ID.
     *
     * @param int $vendorId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($vendorId);
}
