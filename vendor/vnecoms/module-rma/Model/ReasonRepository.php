<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:54 AM
 */
namespace Vnecoms\RMA\Model;

use Vnecoms\RMA\Api\Data;
use Vnecoms\RMA\Api\ReasonRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\RMA\Model\ResourceModel\Reason as ResourceBlock;
use Vnecoms\RMA\Model\ResourceModel\Reason\CollectionFactory as ReasonCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ReasonRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReasonRepository implements ReasonRepositoryInterface
{
    /**
     * @var ResourceBlock
     */
    protected $resource;

    /**
     * @var ReasonFactory
     */
    protected $reasonFactory;

    /**
     * @var ReasonCollectionFactory
     */
    protected $reasonCollectionFactory;

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
     * @var \Vnecoms\RMA\Api\Data\ReasonInterfaceFactory
     */
    protected $dataReasonFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceBlock $resource
     * @param ReasonFactory $reasonFactory
     * @param Data\TemplateInterfaceFactory $dataReasonFactory
     * @param ReasonCollectionFactory $reasonCollectionFactory
     * @param Data\TemplateSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceBlock $resource,
        ReasonFactory $reasonFactory,
        \Vnecoms\RMA\Api\Data\ReasonInterfaceFactory $dataReasonFactory,
        ReasonCollectionFactory $reasonCollectionFactory,
        Data\ReasonSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->reasonFactory = $reasonFactory;
        $this->reasonCollectionFactory = $reasonCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataReasonFactory = $dataReasonFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * Save Reason data
     *
     * @param \Vnecoms\RMA\Api\Data\ReasonInterface $reason
     * @return Block
     * @throws CouldNotSaveException
     */
    public function save(Data\ReasonInterface $reason)
    {
        try {
            $this->resource->save($reason);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $reason;
    }

    /**
     * Load Reason data by given Reason Identity
     *
     * @param string $reasonId
     * @return Reason
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($reasonId)
    {
        $reason = $this->reasonFactory->create();
        $this->resource->load($reason, $reasonId);
        if (!$reason->getId()) {
            throw new NoSuchEntityException(__('Reason with id "%1" does not exist.', $reasonId));
        }
        return $reason;
    }

    /**
     * Load Reason data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Vnecoms\RMA\Model\ResourceModel\Reason\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->reasonCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionReason() ?: 'eq';
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
        /** @var Reason $reasonModel */
        foreach ($collection as $reasonModel) {
            $reasonData = $this->dataReasonFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $reasonData,
                $reasonModel->getData(),
                'Vnecoms\RMA\Api\Data\ReasonInterface'
            );
            $reason[] = $this->dataObjectProcessor->buildOutputDataArray(
                $reasonData,
                'Vnecoms\RMA\Api\Data\ReasonInterface'
            );
        }
        $searchResults->setItems($reason);
        return $searchResults;
    }

    /**
     * Delete Reason
     *
     * @param \Vnecoms\RMA\Api\Data\ReasonInterface $reason
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\ReasonInterface $reason)
    {
        try {
            $this->resource->delete($reason);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete Reason by given Reason Identity
     *
     * @param string $reasonId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($reasonId)
    {
        return $this->delete($this->getById($reasonId));
    }
}
