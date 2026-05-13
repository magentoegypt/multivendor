<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model;

use Vnecoms\Quotation\Api\MessageRepositoryInterface;
use Vnecoms\Quotation\Api\Data\MessageSearchResultsInterfaceFactory;
use Vnecoms\Quotation\Api\Data\MessageInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\Quotation\Model\ResourceModel\Message as ResourceMessage;
use Vnecoms\Quotation\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class MessageRepository implements MessageRepositoryInterface
{

    protected $resource;

    protected $messageFactory;

    protected $messageCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataMessageFactory;

    private $storeManager;


    /**
     * @param ResourceMessage $resource
     * @param MessageFactory $messageFactory
     * @param MessageInterfaceFactory $dataMessageFactory
     * @param MessageCollectionFactory $messageCollectionFactory
     * @param MessageSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceMessage $resource,
        MessageFactory $messageFactory,
        MessageInterfaceFactory $dataMessageFactory,
        MessageCollectionFactory $messageCollectionFactory,
        MessageSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    )
    {
        $this->resource = $resource;
        $this->messageFactory = $messageFactory;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataMessageFactory = $dataMessageFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Vnecoms\Quotation\Api\Data\MessageInterface $message
    )
    {
        /* if (empty($message->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $message->setStoreId($storeId);
        } */
        try {
            $message->getResource()->save($message);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the message: %1',
                $exception->getMessage()
            ));
        }
        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    )
    {
        $collection = $this->messageCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'store_id') {
                    $collection->addStoreFilter($filter->getValue(), false);
                    continue;
                }
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($messageId)
    {
        return $this->delete($this->getById($messageId));
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Vnecoms\Quotation\Api\Data\MessageInterface $message
    )
    {
        try {
            $message->getResource()->delete($message);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Message: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($messageId)
    {
        $message = $this->messageFactory->create();
        $message->getResource()->load($message, $messageId);
        if (!$message->getId()) {
            throw new NoSuchEntityException(__('Message with id "%1" does not exist.', $messageId));
        }
        return $message;
    }
}
