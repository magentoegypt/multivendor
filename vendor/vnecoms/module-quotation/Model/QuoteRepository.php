<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model;

use Vnecoms\Quotation\Api\QuoteRepositoryInterface;
use Vnecoms\Quotation\Api\Data\QuoteSearchResultsInterfaceFactory;
use Vnecoms\Quotation\Api\Data\QuoteInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\Quotation\Model\ResourceModel\Quote as ResourceQuote;
use Vnecoms\Quotation\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class QuoteRepository implements QuoteRepositoryInterface
{

    protected $resource;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    protected $quoteCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataQuoteFactory;

    private $storeManager;

    /**
     * @var []
     */
    protected $quotesByCustomerId = [];

    /**
     * @var []
     */
    protected $quotesById = [];

    /**
     * @param ResourceQuote $resource
     * @param QuoteFactory $quoteFactory
     * @param QuoteInterfaceFactory $dataQuoteFactory
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param QuoteSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceQuote $resource,
        QuoteFactory $quoteFactory,
        QuoteInterfaceFactory $dataQuoteFactory,
        QuoteCollectionFactory $quoteCollectionFactory,
        QuoteSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    )
    {
        $this->resource = $resource;
        $this->quoteFactory = $quoteFactory;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataQuoteFactory = $dataQuoteFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    public function getQuoteById()
    {
        return $this->quotesById;
    }

    public function getQuoteCustomerId()
    {
        return $this->quotesByCustomerId;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
    )
    {
        /* if (empty($quote->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $quote->setStoreId($storeId);
        } */
        try {
            $quote->getResource()->save($quote);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the quote: %1',
                $exception->getMessage()
            ));
        }
        $this->quotesById[$quote->getId()] = $quote;
        return $quote;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    )
    {
        $collection = $this->quoteCollectionFactory->create();
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
    public function deleteById($quoteId)
    {
        $this->delete($this->getById($quoteId));
        unset($this->quotesById[$quoteId]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
    )
    {
        $quoteId = $quote->getId();
        $customerId = $quote->getCustomerId();
        $quote->delete();
        unset($this->quotesById[$quoteId]);
        unset($this->quotesByCustomerId[$customerId]);
    }

    /**
     * {@inheritdoc}
     */
    public function getById($quoteId)
    {
        if (!isset($this->quotesById[$quoteId])) {
            $quote = $this->quoteFactory->create();
            $quote->getResource()->load($quote, $quoteId);
            if (!$quote->getId()) {
                throw new NoSuchEntityException(__('Quote with id "%1" does not exist.', $quoteId));
            }
            $this->quotesById[$quote->getId()] = $quote;
        }
        return $this->quotesById[$quoteId];
    }

    /**
     * Load by Increment Id
     * 
     * @param string $incrementId
     * @throws NoSuchEntityException
     * @return unknown
     */
    public function getByIncrementId($incrementId){
        $quote = $this->quoteFactory->create();
        $quote->load($incrementId, 'increment_id');
        if (!$quote->getId()) {
            throw new NoSuchEntityException(__('Quote with id "%1" does not exist.', $incrementId));
        }
        
        return $quote;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getForCustomer($customerId)
    {
        if (!isset($this->quotesByCustomerId[$customerId])) {
            $quote = $this->quoteFactory->create()->loadByCustomer($customerId);
            $this->quotesById[$quote->getId()] = $quote;
            $this->quotesByCustomerId[$customerId] = $quote;
        }
        return $this->quotesByCustomerId[$customerId];
    }

    /**
     * @param $customerId
     * @return mixed|Quote
     * @throws NoSuchEntityException
     */
    public function getActiveForCustomer($customerId)
    {
        $quote = $this->getForCustomer($customerId);
        if (!$quote->getData('is_active')) {
            throw NoSuchEntityException::singleField('customerId', $customerId);
        }
        return $quote;
    }

    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @throws CouldNotSaveException
     */
    public function submit(\Vnecoms\Quotation\Api\Data\QuoteInterface $quote)
    {
        try {
            $quote->setTotalsCollectedFlag(false);
            $quote->submit();
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the quote: %1',
                $exception->getMessage()
            ));
        }
    }

    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @throws CouldNotSaveException
     */
    public function cancel(\Vnecoms\Quotation\Api\Data\QuoteInterface $quote)
    {
        try {
            if(!$quote->canCancel()) throw new \Exception(__('Could not cancel the quote'));
            $quote->cancel();
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not cancel the quote: %1',
                $exception->getMessage()
            ));
        }
    }

    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @throws CouldNotSaveException
     */
    public function reject(\Vnecoms\Quotation\Api\Data\QuoteInterface $quote)
    {
        try {
            $quote->reject();
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the quote: %1',
                $exception->getMessage()
            ));
        }
    }

    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @throws CouldNotSaveException
     */
    public function accept(\Vnecoms\Quotation\Api\Data\QuoteInterface $quote)
    {
        try {
            $quote->accept();
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not accept the quote: %1',
                $exception->getMessage()
            ));
        }
    }
    
    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @throws CouldNotSaveException
     */
    public function approve(\Vnecoms\Quotation\Api\Data\QuoteInterface $quote)
    {
        try {
            $quote->approve();
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not approve the quote: %1',
                $exception->getMessage()
            ));
        }
    }

    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @throws CouldNotSaveException
     */
    public function hold(\Vnecoms\Quotation\Api\Data\QuoteInterface $quote)
    {
        try {
            $quote->hold();
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the quote: %1',
                $exception->getMessage()
            ));
        }
    }

    /**
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @throws CouldNotSaveException
     */
    public function unHold(\Vnecoms\Quotation\Api\Data\QuoteInterface $quote)
    {
        try {
            $quote->unHold();
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the quote: %1',
                $exception->getMessage()
            ));
        }
    }
}
