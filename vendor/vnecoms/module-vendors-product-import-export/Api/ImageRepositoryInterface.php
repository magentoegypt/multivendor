<?php

namespace Vnecoms\VendorsProductImportExport\Api;

/**
 * Vendor CRUD interface.
 * @api
 */
interface ImageRepositoryInterface
{
    /**
     * @param int $customerId
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageSearchResultInterface
     */
    public function getList($customerId);

    /**
     * Create Vendor
     * @param int $customerId
     * @param \Vnecoms\VendorsProductImportExport\Api\Data\DocumentContentInterface $image
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save($customerId, $image);

    /**
     * Delete visitor by ID.
     * @param int $customerId
     * @param mixed $fileNames
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByFiles($customerId, $fileNames);
}
