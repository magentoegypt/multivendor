<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Api;

use Amasty\Checkout\Api\Data\PlaceholderInterface;
use Magento\Framework\Api\SearchCriteria;

interface PlaceholderRepositoryInterface
{
    /**
     * @param int $placeholderId
     *
     * @return PlaceholderInterface
     */
    public function getById(int $placeholderId): PlaceholderInterface;

    /**
     * @param int $attributeId
     * @param int $storeId
     *
     * @return PlaceholderInterface
     */
    public function getByAttributeIdAndStoreId(int $attributeId, int $storeId): PlaceholderInterface;

    /**
     * @param SearchCriteria $searchCriteria
     *
     * @return array|null
     */
    public function getList(SearchCriteria $searchCriteria): ?array;

    /**
     * @param PlaceholderInterface $placeholderEntity
     *
     * @throws CouldNotDeleteException
     */
    public function delete(PlaceholderInterface $placeholderEntity): void;

    /**
     * @param PlaceholderInterface $placeholderEntity
     *
     * @throws CouldNotSaveException
     */
    public function save(PlaceholderInterface $placeholderEntity): void;
}
