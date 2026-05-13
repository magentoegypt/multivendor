<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Model\ResourceModel;

use Magento\Customer\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Vnecoms\BannerManager\Api\BannerRepositoryInterface;
use Vnecoms\BannerManager\Api\BlockRepositoryInterface;
use Vnecoms\BannerManager\Api\ItemRepositoryInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrder;
//use Vnecoms\BannerManager\Model\Rule\Validator;
use Vnecoms\BannerManager\Api\Data\BlockSearchResultsInterfaceFactory;
use Vnecoms\BannerManager\Api\Data\BlockInterfaceFactory;

use Vnecoms\BannerManager\Api\Data\BannerInterface;
use Vnecoms\BannerManager\Model\Source\Status;
use Vnecoms\BannerManager\Api\Data\ItemInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime as StdlibDateTime;
use Vnecoms\BannerManager\Api\Data\BlockSearchResultsInterface;
use Vnecoms\BannerManager\Api\Data\BlockInterface;

/**
 * Class BlockRepository
 *
 * @package Vnecoms\BannerManager\Model\ResourceModel
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BlockRepository implements BlockRepositoryInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var BannerRepositoryInterface
     */
    private $bannerRepository;

    /**
     * @var ItemRepositoryInterface
     */
    private $itemRepository;


    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var BlockSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var BlockInterfaceFactory
     */
    private $blockFactory;


    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param BannerRepositoryInterface $bannerRepository
     * @param ItemRepositoryInterface $itemRepository
     * @param SortOrderBuilder $sortOrderBuilder
     * @param BlockSearchResultsInterfaceFactory $searchResultsFactory
     * @param BlockInterfaceFactory $blockFactory
     * @param DateTime $dateTime
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Session $customerSession,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        BannerRepositoryInterface $bannerRepository,
        ItemRepositoryInterface $itemRepository,
        SortOrderBuilder $sortOrderBuilder,
        BlockSearchResultsInterfaceFactory $searchResultsFactory,
        BlockInterfaceFactory $blockFactory,
        DateTime $dateTime
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->bannerRepository = $bannerRepository;
        $this->itemRepository = $itemRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->blockFactory = $blockFactory;
        $this->dateTime = $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getList($blockType, $blockPosition = null, $allBlocks = true, $updateViewsStatistic = true)
    {
        $this->searchCriteriaBuilder
            ->addFilter(BannerInterface::STATUS, Status::STATUS_ENABLED);
            //->addFilter(BannerInterface::PAGE_TYPE, $blockType)
            //->addFilter(BannerInterface::POSITION, $blockPosition);
        $bannerList = $this->bannerRepository
            ->getList($this->searchCriteriaBuilder->create())
            ->getItems();

        $blockItems = [];
        foreach ($bannerList as $banner) {
            if (count($banner->getItemIds()) /*&& $this->validator->canShow($banner)*/) {
                $itemList = $this->getItemList($banner);
                foreach ($itemList as $item) {
                    $this->searchCriteriaBuilder
                        ->addFilter('banner_id', $banner->getId())
                        ->addFilter('item_id', $item->getId());
                }
                //$this->customerStatisticManager->save();

                /** @var BlockInterface $blockDataModel */
                $blockDataModel = $this->blockFactory->create();
                $blockDataModel->setBanner($banner);
                $blockDataModel->setItems($itemList);
                $blockItems[] = $blockDataModel;

                // Break of the loop if we want to display only one block
                if (!$allBlocks) {
                    break;
                }
            }
        }

        /** @var BlockSearchResultsInterface $blockSearchResults */
        $blockSearchResults = $this->searchResultsFactory->create()
            ->setSearchCriteria($this->searchCriteriaBuilder->create());
        $blockSearchResults->setItems($blockItems);
        $blockSearchResults->setTotalCount(count($blockItems));

        return $blockSearchResults;
    }

    /**
     * Retrieve item list for current conditions
     *
     * @param BannerInterface $banner
     * @return ItemInterface[]
     */
    private function getItemList($banner)
    {
        $currentDate = $this->dateTime->gmtDate(StdlibDateTime::DATETIME_PHP_FORMAT);

        $directionSort = $banner->getIsRandomOrderImage()
            ? SortOrder::SORT_DESC // For RAND sorting
            : SortOrder::SORT_ASC;
        $sortOrder = $this->sortOrderBuilder
            ->setField('position')
            ->setDirection($directionSort)
            ->create();

        $this->searchCriteriaBuilder
            ->addFilter(ItemInterface::STATUS, Status::STATUS_ENABLED)
            ->addFilter('date', $currentDate)
            ->addFilter('banner_id', $banner->getId())
            ->addSortOrder($sortOrder);

        $itemList = $this->itemRepository
            ->getList($this->searchCriteriaBuilder->create())
            ->getItems();

        return $itemList;
    }
}
