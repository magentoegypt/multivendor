<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model;

use Vnecoms\VendorsCms\Api\Data;
use Vnecoms\VendorsCms\Api\PageRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\VendorsCms\Model\ResourceModel\Page as ResourcePage;
use Vnecoms\VendorsCms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;

/**
 * Class PageRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PageRepository implements PageRepositoryInterface
{
    /**
     * @var ResourcePage
     */
    protected $resource;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var PageCollectionFactory
     */
    protected $pageCollectionFactory;

    /**
     * @var Data\PageSearchResultsInterfaceFactory
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
     * @var \Vnecoms\VendorsCms\Api\Data\PageInterfaceFactory
     */
    protected $dataPageFactory;

    /**
     * @var \Vnecoms\VendorsApi\Helper\Data
     */
    protected $helperApi;

    /**
     * PageRepository constructor.
     * @param ResourcePage $resource
     * @param PageFactory $pageFactory
     * @param Data\PageInterfaceFactory $dataPageFactory
     * @param PageCollectionFactory $pageCollectionFactory
     * @param Data\PageSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param \Vnecoms\VendorsApi\Helper\Data $helperApi
     */
    public function __construct(
        ResourcePage $resource,
        PageFactory $pageFactory,
        Data\PageInterfaceFactory $dataPageFactory,
        PageCollectionFactory $pageCollectionFactory,
        Data\PageSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        \Vnecoms\VendorsApi\Helper\Data $helperApi
    ) {
        $this->resource = $resource;
        $this->pageFactory = $pageFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPageFactory = $dataPageFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->helperApi = $helperApi;
    }

    /**
     * @param Data\PageInterface $customerId
     * @param Data\PageInterface $page
     * @return Data\PageInterface
     * @throws CouldNotSaveException
     */
    public function save($customerId, \Vnecoms\VendorsCms\Api\Data\PageInterface $page)
    {
        try {
            $customer   = $this->helperApi->getCustomer($customerId);
            $vendorModel     = $this->helperApi->getVendorByCustomer($customer);

            if ($page->getId()) {
                $checkPage = $this->getById($page->getId());
                if ($checkPage->getVendorId() != $vendorModel->getId()) {
                    throw new NoSuchEntityException(__('CMS Page with id "%1" does not exist.', $page->getId()));
                }
            }

            $page->setVendorId($vendorModel->getId());
            $this->resource->save($page);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the page: %1',
                $exception->getMessage()
            ));
        }

        return $page;
    }

    /**
     * Load Page data by given Page Identity.
     *
     * @param string $pageId
     *
     * @return Page
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($pageId)
    {
        $page = $this->pageFactory->create();
        $page->load($pageId);
        if (!$page->getId()) {
            throw new NoSuchEntityException(__('CMS Page with id "%1" does not exist.', $pageId));
        }

        return $page;
    }

    /**
     * Load Page data collection by given search criteria.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     *
     * @return \Vnecoms\VendorsCms\Model\ResourceModel\Page\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->pageCollectionFactory->create();
        $collection->clear()->getSelect()->reset(\Zend_Db_Select::WHERE);;
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $searchResults->setTotalCount($collection->getSize());
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

        $pages = [];
        /** @var Page $pageModel */
        foreach ($collection as $pageModel) {
            $pageData = $this->dataPageFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $pageData,
                $pageModel->getData(),
                'Vnecoms\VendorsCms\Api\Data\PageInterface'
            );
            $pages[] = $this->dataObjectProcessor->buildOutputDataArray(
                $pageData,
                'Vnecoms\VendorsCms\Api\Data\PageInterface'
            );
        }
        $searchResults->setItems($pages);

        return $searchResults;
    }

    /**
     * Delete Page.
     *
     * @param \Vnecoms\VendorsCms\Api\Data\PageInterface $page
     *
     * @return bool
     *
     * @throws CouldNotDeleteException
     */
    public function delete(\Vnecoms\VendorsCms\Api\Data\PageInterface $page)
    {
        try {
            $this->resource->delete($page);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the page: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }

    /**
     * @param int $customerId
     * @param $pageId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function deleteById($customerId, $pageId)
    {
        $customer   = $this->helperApi->getCustomer($customerId);
        $vendorModel     = $this->helperApi->getVendorByCustomer($customer);

        $page = $this->getById($pageId);

        if ($page->getVendorId() != $vendorModel->getId()) {
            throw new NoSuchEntityException(__('Role with id "%1" does not exist.',$pageId));
        }
        return $this->delete($this->getById($pageId));
    }
}
