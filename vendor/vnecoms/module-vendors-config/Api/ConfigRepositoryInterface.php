<?php

namespace Vnecoms\VendorsConfig\Api;

/**
 * Vendor CRUD interface.
 * @api
 */
interface ConfigRepositoryInterface
{
    /**
     * admin save config
     *
     * @param string $vendorId
     * @param string $path
     * @param mixed $value
     * @param int $storeId
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveByVendorId($vendorId, $path, $value, $storeId = 0);

    /**
     * @param int $customerId
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigSearchResultInterface
     */
    public function getList($customerId, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * admin save config
     *
     * @param string $customerId
     * @param string $path
     * @param mixed $value
     * @param int $storeId
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveByCustomerId($customerId, $path, $value, $storeId = 0);
}
