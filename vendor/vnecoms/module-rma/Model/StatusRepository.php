<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Model;

use Vnecoms\RMA\Api\Data;
use Vnecoms\RMA\Api\StatusRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\RMA\Model\ResourceModel\Status as ResourceBlock;
use Vnecoms\RMA\Model\ResourceModel\Status\CollectionFactory as StatusCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class StatusRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StatusRepository implements StatusRepositoryInterface
{
    /**
     * @var ResourceBlock
     */
    protected $resource;

    /**
     * @var StatusFactory
     */
    protected $statusFactory;

    /**
     * @var StatusCollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * @var Data\TemplateSearchResultsInterfaceFactory
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
     * @var \Vnecoms\HelpDesk\Api\Data\StatusInterfaceFactory
     */
    protected $dataStatusFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceBlock $resource
     * @param StatusFactory $statusFactory
     * @param Data\TemplateInterfaceFactory $dataStatusFactory
     * @param StatusCollectionFactory $statusCollectionFactory
     * @param Data\TemplateSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceBlock $resource,
        StatusFactory $statusFactory,
        \Vnecoms\RMA\Api\Data\StatusInterfaceFactory $dataStatusFactory,
        StatusCollectionFactory $statusCollectionFactory,
        Data\StatusSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->statusFactory = $statusFactory;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataStatusFactory = $dataStatusFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * Save Status data
     *
     * @param \Vnecoms\RMA\Api\Data\StatusInterface $status
     * @return Block
     * @throws CouldNotSaveException
     */
    public function save(Data\StatusInterface $status)
    {
        try {
            $this->resource->save($status);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $status;
    }

    /**
     * Load Status data by given Status Identity
     *
     * @param string $statusId
     * @return Status
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($statusId)
    {
        $status = $this->statusFactory->create();
        $this->resource->load($status, $statusId);
        if (!$status->getId()) {
            throw new NoSuchEntityException(__('Status with id "%1" does not exist.', $statusId));
        }
        return $status;
    }

    /**
     * Load Status data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Vnecoms\HelpDesk\Model\ResourceModel\Template\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
    
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->statusCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionStatus() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
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
        $template = [];
        /** @var Status $statusModel */
        foreach ($collection as $statusModel) {
            $statusData = $this->dataStatusFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $statusData,
                $statusModel->getData(),
                'Vnecoms\RMA\Api\Data\StatusInterface'
            );
            $status[] = $this->dataObjectProcessor->buildOutputDataArray(
                $statusData,
                'Vnecoms\RMA\Api\Data\StatusInterface'
            );
        }
        $searchResults->setItems($status);
        return $searchResults;
    }

    /**
     * Delete Status
     *
     * @param \Vnecoms\RMA\Api\Data\StatusInterface $status
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\StatusInterface $status)
    {
        try {
            $this->resource->delete($status);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete Status by given Status Identity
     *
     * @param string $statusId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($statusId)
    {
        return $this->delete($this->getById($statusId));
    }
}
