<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:54 AM
 */
namespace Vnecoms\RMA\Model;

use Vnecoms\RMA\Api\Data;
use Vnecoms\RMA\Api\ReponseRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\RMA\Model\ResourceModel\Reponse as ResourceBlock;
use Vnecoms\RMA\Model\ResourceModel\Reponse\CollectionFactory as ReponseCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ReponseRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReponseRepository implements ReponseRepositoryInterface
{
    /**
     * @var ResourceBlock
     */
    protected $resource;

    /**
     * @var ReponseFactory
     */
    protected $reponseFactory;

    /**
     * @var ReponseCollectionFactory
     */
    protected $reponseCollectionFactory;

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
     * @var \Vnecoms\HelpDesk\Api\Data\ReponseInterfaceFactory
     */
    protected $dataReponseFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceBlock $resource
     * @param ReponseFactory $reponseFactory
     * @param Data\TemplateInterfaceFactory $dataReponseFactory
     * @param ReponseCollectionFactory $reponseCollectionFactory
     * @param Data\TemplateSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceBlock $resource,
        ReponseFactory $reponseFactory,
        \Vnecoms\RMA\Api\Data\ReponseInterfaceFactory $dataReponseFactory,
        ReponseCollectionFactory $reponseCollectionFactory,
        Data\ReponseSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->reponseFactory = $reponseFactory;
        $this->reponseCollectionFactory = $reponseCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataReponseFactory = $dataReponseFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * Save Reponse data
     *
     * @param \Vnecoms\RMA\Api\Data\ReponseInterface $reponse
     * @return Block
     * @throws CouldNotSaveException
     */
    public function save(Data\ReponseInterface $reponse)
    {
        try {
            $this->resource->save($reponse);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $reponse;
    }

    /**
     * Load Reponse data by given Reponse Identity
     *
     * @param string $reponseId
     * @return Reponse
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($reponseId)
    {
        $reponse = $this->reponseFactory->create();
        $this->resource->load($reponse, $reponseId);
        if (!$reponse->getId()) {
            throw new NoSuchEntityException(__('Reponse with id "%1" does not exist.', $reponseId));
        }
        return $reponse;
    }

    /**
     * Load Reponse data collection by given search criteria
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

        $collection = $this->reponseCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionReponse() ?: 'eq';
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
        /** @var Reponse $reponseModel */
        foreach ($collection as $reponseModel) {
            $reponseData = $this->dataReponseFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $reponseData,
                $reponseModel->getData(),
                'Vnecoms\RMA\Api\Data\ReponseInterface'
            );
            $reponse[] = $this->dataObjectProcessor->buildOutputDataArray(
                $reponseData,
                'Vnecoms\RMA\Api\Data\ReponseInterface'
            );
        }
        $searchResults->setItems($reponse);
        return $searchResults;
    }

    /**
     * Delete Reponse
     *
     * @param \Vnecoms\HelpDesk\Api\Data\ReponseInterface $reponse
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\ReponseInterface $reponse)
    {
        try {
            $this->resource->delete($reponse);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete Reponse by given Reponse Identity
     *
     * @param string $reponseId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($reponseId)
    {
        return $this->delete($this->getById($reponseId));
    }
}
