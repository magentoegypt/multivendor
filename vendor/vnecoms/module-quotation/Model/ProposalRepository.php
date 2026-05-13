<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model;

use Vnecoms\Quotation\Api\ProposalRepositoryInterface;
use Vnecoms\Quotation\Api\Data\ProposalSearchResultsInterfaceFactory;
use Vnecoms\Quotation\Api\Data\ProposalInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\Quotation\Model\ResourceModel\Proposal as ResourceProposal;
use Vnecoms\Quotation\Model\ResourceModel\Proposal\CollectionFactory as ProposalCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class ProposalRepository implements ProposalRepositoryInterface
{

    protected $resource;

    protected $proposalFactory;

    protected $proposalCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataProposalFactory;

    private $storeManager;


    /**
     * @param ResourceProposal $resource
     * @param ProposalFactory $proposalFactory
     * @param ProposalInterfaceFactory $dataProposalFactory
     * @param ProposalCollectionFactory $proposalCollectionFactory
     * @param ProposalSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceProposal $resource,
        ProposalFactory $proposalFactory,
        ProposalInterfaceFactory $dataProposalFactory,
        ProposalCollectionFactory $proposalCollectionFactory,
        ProposalSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    )
    {
        $this->resource = $resource;
        $this->proposalFactory = $proposalFactory;
        $this->proposalCollectionFactory = $proposalCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataProposalFactory = $dataProposalFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Vnecoms\Quotation\Api\Data\ProposalInterface $proposal
    )
    {
        /* if (empty($proposal->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $proposal->setStoreId($storeId);
        } */
        try {
            $proposal->getResource()->save($proposal);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the proposal: %1',
                $exception->getMessage()
            ));
        }
        return $proposal;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    )
    {
        $collection = $this->proposalCollectionFactory->create();
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
    public function deleteById($proposalId)
    {
        return $this->delete($this->getById($proposalId));
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Vnecoms\Quotation\Api\Data\ProposalInterface $proposal
    )
    {
        try {
            $proposal->getResource()->delete($proposal);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Proposal: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($proposalId)
    {
        $proposal = $this->proposalFactory->create();
        $proposal->getResource()->load($proposal, $proposalId);
        if (!$proposal->getId()) {
            throw new NoSuchEntityException(__('Proposal with id "%1" does not exist.', $proposalId));
        }
        return $proposal;
    }
}
