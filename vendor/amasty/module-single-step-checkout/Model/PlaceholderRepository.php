<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model;

use Amasty\Checkout\Api\Data\PlaceholderInterface;
use Amasty\Checkout\Model\ResourceModel\Placeholder as ResourcePlaceholder;
use Amasty\Checkout\Model\ResourceModel\Placeholder\CollectionFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;

class PlaceholderRepository
{
    /**
     * @var CollectionFactory
     */
    private $collection;

    /**
     * @var ResourcePlaceholder
     */
    private $resourcePlaceholder;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var PlaceholderFactory
     */
    private $placeholderFactory;

    /**
     * @var PlaceholderInterface[]
     */
    private $entitiesById = [];

    /**
     * @var array[]
     */
    private $entitiesByAttributeId = [];

    public function __construct(
        CollectionFactory $collectionFactory,
        ResourcePlaceholder $resourcePlaceholder,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        PlaceholderFactory $placeholderFactory
    ) {
        $this->collection = $collectionFactory;
        $this->resourcePlaceholder = $resourcePlaceholder;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->placeholderFactory = $placeholderFactory;
    }

    /**
     * @param int $placeholderId
     *
     * @return PlaceholderInterface
     */
    public function getById(int $placeholderId): PlaceholderInterface
    {
        if ($placeholderId === 0) {
            return $this->placeholderFactory->create();
        }
        if (!isset($this->entitiesById[$placeholderId])) {
            $placeholderEntity = $this->placeholderFactory->create();
            $this->resourcePlaceholder->load($placeholderEntity, $placeholderId);
            $this->entitiesById[$placeholderId] = $placeholderEntity;
        }

        return $this->entitiesById[$placeholderId];
    }

    /**
     * @param int $attributeId
     * @param int $storeId
     * @return PlaceholderInterface
     * @throws LocalizedException
     */
    public function getByAttributeIdAndStoreId(int $attributeId, int $storeId): PlaceholderInterface
    {
        if (!isset($this->entitiesByAttributeId[$attributeId][$storeId])) {
            $placeholderEntity = $this->placeholderFactory->create();
            $this->resourcePlaceholder->loadByAttributeIdAndStoreId($placeholderEntity, $attributeId, $storeId);
            $this->entitiesByAttributeId[$attributeId][$storeId] = $placeholderEntity;
        }

        return $this->entitiesByAttributeId[$attributeId][$storeId];
    }

    /**
     * @param SearchCriteria $searchCriteria
     *
     * @return array
     */
    public function getList(SearchCriteria $searchCriteria): array
    {
        $collection = $this->collection->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        
        return $searchResults->getItems();
    }

    /**
     * @param PlaceholderInterface $placeholderEntity
     *
     * @throws CouldNotDeleteException
     */
    public function delete(PlaceholderInterface $placeholderEntity): void
    {
        try {
            $this->resourcePlaceholder->delete($placeholderEntity);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not save the placeholder: %1',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @param PlaceholderInterface $placeholderEntity
     *
     * @throws CouldNotSaveException
     */
    public function save(PlaceholderInterface $placeholderEntity): void
    {
        try {
            $this->resourcePlaceholder->save($placeholderEntity);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the placeholder: %1',
                    $exception->getMessage()
                )
            );
        }
    }
}
