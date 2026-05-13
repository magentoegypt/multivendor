<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Model;

use Vnecoms\RMA\Api\Data;
use Vnecoms\RMA\Api\RequestRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\RMA\Model\ResourceModel\Request as ResourceBlock;
use Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory as RequestCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class RequestRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequestRepository implements RequestRepositoryInterface
{
    /**
     * @var ResourceBlock
     */
    protected $resource;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var RequestCollectionFactory
     */
    protected $requestCollectionFactory;

    /**
     * @var Data\RequestSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var \Vnecoms\RMA\Api\Data\RequestInterfaceFactory
     */
    protected $dataRequestFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceBlock $resource
     * @param RequestFactory $requestFactory
     * @param Data\RequestInterfaceFactory $dataRequestFactory
     * @param RequestCollectionFactory $requestCollectionFactory
     * @param Data\RequestSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceBlock $resource,
        RequestFactory $requestFactory,
        \Vnecoms\RMA\Api\Data\RequestInterfaceFactory $dataRequestFactory,
        RequestCollectionFactory $requestCollectionFactory,
        Data\RequestSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->requestFactory = $requestFactory;
        $this->requestCollectionFactory = $requestCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataRequestFactory = $dataRequestFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * Save Request data
     *
     * @param \Vnecoms\RMA\Api\Data\RequestInterface $request
     * @return Block
     * @throws CouldNotSaveException
     */
    public function save(Data\RequestInterface $request)
    {
        $websiteId = $this->storeManager->getWebsite()->getId();
        $request->setWebsiteId($websiteId);
        try {
            $this->resource->save($request);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $request;
    }

    /**
     * Load Request data by given Request Identity
     *
     * @param string $requestId
     * @return Block
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($requestId)
    {
        $request = $this->requestFactory->create();
        $this->resource->load($request, $requestId);
        if (!$request->getId()) {
            throw new NoSuchEntityException(__('Request with id "%1" does not exist.', $requestId));
        }
        return $request;
    }

    /**
     * Load Request data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Vnecoms\RMA\Model\ResourceModel\Request\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->requestCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addAttributeToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $request = [];
        /** @var Request $requestModel */
        foreach ($collection as $requestModel) {
            $requestData = $this->dataRequestFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $requestData,
                $requestModel->getData(),
                'Vnecoms\RMA\Api\Data\RequestInterface'
            );
            $request[] = $this->dataObjectProcessor->buildOutputDataArray(
                $requestData,
                'Vnecoms\RMA\Api\Data\RequestInterface'
            );
        }
        $searchResults->setItems($request);
        return $searchResults;
    }

    /**
     * Delete Request
     *
     * @param \Vnecoms\RMA\Api\Data\RequestInterface $request
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\RequestInterface $request)
    {
        try {
            $this->resource->delete($request);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete Request by given Ticket Identity
     *
     * @param string $requestId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($requestId)
    {
        return $this->delete($this->getById($requestId));
    }
}
