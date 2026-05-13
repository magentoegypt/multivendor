<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model;

use Vnecoms\VendorsCms\Api\Data;
use Vnecoms\VendorsCms\Api\BlockRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\VendorsCms\Model\ResourceModel\Block as ResourceBlock;
use Vnecoms\VendorsCms\Model\ResourceModel\Block\CollectionFactory as BlockCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class BlockRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BlockRepository implements BlockRepositoryInterface
{
    /**
     * @var ResourceBlock
     */
    protected $resource;

    /**
     * @var BlockFactory
     */
    protected $blockFactory;

    /**
     * @var BlockCollectionFactory
     */
    protected $blockCollectionFactory;

    /**
     * @var Data\BlockSearchResultsInterfaceFactory
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
     * @var \Vnecoms\VendorsCms\Api\Data\BlockInterfaceFactory
     */
    protected $dataBlockFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Vnecoms\VendorsApi\Helper\Data
     */
    protected $helperApi;

    /**
     * @param ResourceBlock                           $resource
     * @param BlockFactory                            $blockFactory
     * @param Data\BlockInterfaceFactory              $dataBlockFactory
     * @param BlockCollectionFactory                  $blockCollectionFactory
     * @param Data\BlockSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper                        $dataObjectHelper
     * @param DataObjectProcessor                     $dataObjectProcessor
     * @param StoreManagerInterface                   $storeManager
     */
    public function __construct(
        ResourceBlock $resource,
        BlockFactory $blockFactory,
        \Vnecoms\VendorsCms\Api\Data\BlockInterfaceFactory $dataBlockFactory,
        BlockCollectionFactory $blockCollectionFactory,
        Data\BlockSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        \Vnecoms\VendorsApi\Helper\Data $helperApi
    ) {
        $this->resource = $resource;
        $this->blockFactory = $blockFactory;
        $this->blockCollectionFactory = $blockCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataBlockFactory = $dataBlockFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->helperApi = $helperApi;
    }

    /**
     * @param Data\BlockInterface $customerId
     * @param Data\BlockInterface $block
     * @return Data\BlockInterface
     * @throws CouldNotSaveException
     */
    public function save($customerId, Data\BlockInterface $block)
    {
        /*$storeId = $this->storeManager->getStore()->getId();
        $block->setStoreId($storeId);*/
        try {
            $customer   = $this->helperApi->getCustomer($customerId);
            $vendorModel     = $this->helperApi->getVendorByCustomer($customer);

            if ($block->getId()) {
                $checkPage = $this->getById($block->getId());
                if ($checkPage->getVendorId() != $vendorModel->getId()) {
                    throw new NoSuchEntityException(__('CMS Block with id "%1" does not exist.', $block->getId()));
                }
            }
            $block->setVendorId($vendorModel->getId());
            $this->resource->save($block);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $block;
    }

    /**
     * Load Block data by given Block Identity.
     *
     * @param string $blockId
     *
     * @return Block
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($blockId)
    {
        $block = $this->blockFactory->create();
        $this->resource->load($block, $blockId);
        if (!$block->getId()) {
            throw new NoSuchEntityException(__('CMS Block with id "%1" does not exist.', $blockId));
        }

        return $block;
    }

    /**
     * Load Block data collection by given search criteria.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     *
     * @return \Vnecoms\VendorsCms\Model\ResourceModel\Block\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->blockCollectionFactory->create();
        $collection->clear()->getSelect()->reset(\Zend_Db_Select::WHERE);;
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
        $blocks = [];
        /** @var Block $blockModel */
        foreach ($collection as $blockModel) {
            $blockData = $this->dataBlockFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $blockData,
                $blockModel->getData(),
                'Vnecoms\VendorsCms\Api\Data\BlockInterface'
            );
            $blocks[] = $this->dataObjectProcessor->buildOutputDataArray(
                $blockData,
                'Vnecoms\VendorsCms\Api\Data\BlockInterface'
            );
        }
        $searchResults->setItems($blocks);

        return $searchResults;
    }

    /**
     * Delete Block.
     *
     * @param \Vnecoms\VendorsCms\Api\Data\BlockInterface $block
     *
     * @return bool
     *
     * @throws CouldNotDeleteException
     */
    public function delete(Data\BlockInterface $block)
    {
        try {
            $this->resource->delete($block);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param int $customerId
     * @param $blockId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function deleteById($customerId, $blockId)
    {
        $customer   = $this->helperApi->getCustomer($customerId);
        $vendorModel     = $this->helperApi->getVendorByCustomer($customer);

        $page = $this->getById($blockId);

        if ($page->getVendorId() != $vendorModel->getId()) {
            throw new NoSuchEntityException(__('Role with id "%1" does not exist.',$blockId));
        }
        return $this->delete($this->getById($blockId));
    }
}
