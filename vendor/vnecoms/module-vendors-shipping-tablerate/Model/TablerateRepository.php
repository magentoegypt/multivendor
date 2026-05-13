<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:54 AM
 */
namespace Vnecoms\VendorsShippingTableRate\Model;

use Vnecoms\VendorsShippingTableRate\Api\Data;
use Vnecoms\VendorsShippingTableRate\Api\TablerateRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\VendorsShippingTableRate\Model\ResourceModel\Tablerate as RateBlock;
use Vnecoms\VendorsShippingTableRate\Model\ResourceModel\Tablerate\CollectionFactory as RateCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class TablerateRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TablerateRepository implements TablerateRepositoryInterface
{
    /**
     * @var RateBlock
     */
    protected $resource;

    /**
     * @var TablerateFactory
     */
    protected $tablerateFactory;

    /**
     * @var TablerateCollectionFactory
     */
    protected $tablerateCollectionFactory;

    /**
     * @var Data\TablerateSearchResultsInterfaceFactory
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
     * @var \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterfaceFactory
     */
    protected $dataRateFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Vnecoms\VendorsApi\Helper\Data
     */
    protected $helperApi;

    /**
     * TablerateRepository constructor.
     * @param RateBlock $resource
     * @param TablerateFactory $tablerateFactory
     * @param Data\TablerateInterfaceFactory $dataRateFactory
     * @param RateCollectionFactory $rateCollectionFactory
     * @param Data\TablerateSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param \Vnecoms\VendorsApi\Helper\Data $helperApi
     */
    public function __construct(
        RateBlock $resource,
        TablerateFactory $tablerateFactory,
        \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterfaceFactory $dataRateFactory,
        RateCollectionFactory $rateCollectionFactory,
        Data\TablerateSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        \Vnecoms\VendorsApi\Helper\Data $helperApi
    ) {
        $this->resource = $resource;
        $this->tablerateFactory = $tablerateFactory;
        $this->tablerateCollectionFactory = $rateCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataRateFactory = $dataRateFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->helperApi = $helperApi;
    }

    /**
     * @param Data\TablerateInterface $customerId
     * @param Data\TablerateInterface $rate
     * @return Data\TablerateInterface
     * @throws CouldNotSaveException
     */
    public function save($customerId, Data\TablerateInterface $rate)
    {
        try {
            $customer   = $this->helperApi->getCustomer($customerId);
            $vendorModel     = $this->helperApi->getVendorByCustomer($customer);
            $rate->setVendorId($vendorModel->getId());
            $errors = $this->resource->validate($rate);

            if(count($errors)){
                throw new \Exception(implode("<br />", $errors));
            }

            $this->resource->save($rate);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $rate;
    }

    /**
     * Load Reponse data by given rate Identity
     *
     * @param string $rateId
     * @return Reponse
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($rateId)
    {
        $rate = $this->tablerateFactory->create();
        $this->resource->load($rate, $rateId);
        if (!$rate->getId()) {
            throw new NoSuchEntityException(__('Reponse with id "%1" does not exist.', $rateId));
        }
        return $rate;
    }

    /**
     * Load Rate data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Vnecoms\VendorsShippingTableRate\Model\ResourceModel\Tablerate\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->tablerateCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
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
        $rate = [];
        /** @var Tablerate $tablerateModel */
        foreach ($collection as $tablerateModel) {
            $rateData = $this->dataRateFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $rateData,
                $tablerateModel->getData(),
                'Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface'
            );
            $rate[] = $this->dataObjectProcessor->buildOutputDataArray(
                $rateData,
                'Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface'
            );
        }
        $searchResults->setItems($rate);
        return $searchResults;
    }

    /**
     * Delete Reponse
     *
     * @param \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface $rate
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\TablerateInterface $rate)
    {
        try {
            $this->resource->delete($rate);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete rate by given Reponse Identity
     * @param string $customerId
     * @param string $rateId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($customerId, $rateId)
    {
       try {
            $customer   = $this->helperApi->getCustomer($customerId);
            $vendorModel     = $this->helperApi->getVendorByCustomer($customer);

            $rate = $this->getById($rateId);

            if ($rate->getVendorId() != $vendorModel->getId()) {
                throw new NoSuchEntityException(__('Rate with id "%1" does not exist.',$rateId));
            }
            return $this->delete($this->getById($rateId));
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

    }
}
