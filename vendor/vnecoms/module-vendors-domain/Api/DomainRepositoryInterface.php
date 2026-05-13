<?php

namespace Vnecoms\VendorsDomain\Api;

/**
 * Vendor CRUD interface.
 * @api
 */
interface DomainRepositoryInterface
{
    /**
     * Create domain
     *
     * @param \Vnecoms\VendorsDomain\Api\Data\DomainInterface $domain
     * @return \Vnecoms\VendorsDomain\Api\Data\DomainInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Vnecoms\VendorsDomain\Api\Data\DomainInterface $domain);

    /**
     * Delete domain by domain ID.
     *
     * @param int $domainId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($domainId);
}
