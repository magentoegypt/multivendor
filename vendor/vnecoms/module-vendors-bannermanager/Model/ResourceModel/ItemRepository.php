<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Model\ResourceModel;

use Vnecoms\BannerManager\Api\Data\ItemInterface;
use Vnecoms\BannerManager\Api\Data\ItemInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Vnecoms\BannerManager\Model\ItemRegistry;
use Vnecoms\BannerManager\Api\Data\ItemSearchResultsInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Vnecoms\BannerManager\Api\Data\ItemSearchResultsInterface;
use Vnecoms\BannerManager\Model\Item as ItemModel;
use Magento\Framework\EntityManager\EntityManager;
use Vnecoms\BannerManager\Model\ItemFactory;

/**
 * Class ItemRepository
 * @package Vnecoms\BannerManager\Model\ResourceModel
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemRepository implements \Vnecoms\BannerManager\Api\ItemRepositoryInterface
{
    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ItemInterfaceFactory
     */
    private $itemDataFactory;

    /**
     * @var ItemRegistry
     */
    private $itemRegistry;

    /**
     * @var ItemSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @param ItemFactory $itemFactory
     * @param EntityManager $entityManager
     * @param ItemInterfaceFactory $itemDataFactory
     * @param ItemRegistry $itemRegistry
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param ItemSearchResultsInterfaceFactory $searchResultsFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     */
    public function __construct(
        ItemFactory $itemFactory,
        EntityManager $entityManager,
        ItemInterfaceFactory $itemDataFactory,
        ItemRegistry $itemRegistry,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        ItemSearchResultsInterfaceFactory $searchResultsFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    ) {
        $this->itemFactory = $itemFactory;
        $this->entityManager = $entityManager;
        $this->itemDataFactory = $itemDataFactory;
        $this->itemRegistry = $itemRegistry;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ItemInterface $item)
    {
        $item = $this->entityManager->save($item);
        $this->itemRegistry->push($item);
        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function get($itemId)
    {
        return $this->itemRegistry->retrieve($itemId);
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var ItemSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create()
            ->setSearchCriteria($searchCriteria);
        /** @var \Vnecoms\BannerManager\Model\ResourceModel\Item\Collection $collection */
        $collection = $this->itemFactory->create()->getCollection();
        $this->extensionAttributesJoinProcessor->process($collection, ItemInterface::class);
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() == 'banner_id') {
                    $collection->addBannerFilter($filter->getValue());
                } else {
                    $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                    $fields[] = $filter->getField();
                    $conditions[] = [$condition => $filter->getValue()];
                }
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        if ($sortOrders = $searchCriteria->getSortOrders()) {
            /** @var \Magento\Framework\Api\SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder($sortOrder->getField(), $sortOrder->getDirection());
            }
        }

        $collection
            ->setCurPage($searchCriteria->getCurrentPage())
            ->setPageSize($searchCriteria->getPageSize());

        $items = [];
        /** @var ItemModel $itemModel */
        foreach ($collection as $itemModel) {
            $items[] = $this->getItemDataObject($itemModel);
        }
        $searchResults->setItems($items);
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ItemInterface $item)
    {
        return $this->deleteById($item->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($itemId)
    {
        $item = $this->itemRegistry->retrieve($itemId);
        $this->entityManager->delete($item);
        $this->itemRegistry->remove($itemId);
        return true;
    }

    /**
     * Retrieves item data object using Item Model
     *
     * @param ItemModel $item
     * @return ItemInterface
     */
    private function getItemDataObject(ItemModel $item)
    {
        /** @var ItemInterface $itemDataObject */
        $itemDataObject = $this->itemDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $itemDataObject,
            $item->getData(),
            ItemInterface::class
        );
        return $itemDataObject;
    }
}
