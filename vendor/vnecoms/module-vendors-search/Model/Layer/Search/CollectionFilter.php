<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSearch\Model\Layer\Search;

use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Layer\CollectionFilterInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Search\Model\QueryFactory;
use Vnecoms\VendorsSearch\Model\QueryFactory as VendorQueryFactory;

class CollectionFilter extends \Magento\Catalog\Model\Layer\Search\CollectionFilter
{

    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    protected $queryFactory;

    /**
     * @var \Vnecoms\VendorsSearch\Model\QueryFactory
     */
    protected $vendorQueryFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param Config $catalogConfig
     * @param StoreManagerInterface $storeManager
     * @param Visibility $productVisibility
     */
    public function __construct(
        Config $catalogConfig,
        StoreManagerInterface $storeManager,
        Visibility $productVisibility,
        QueryFactory $queryFactory,
        VendorQueryFactory $vendorQueryFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->queryFactory = $queryFactory;
        $this->vendorQueryFactory = $vendorQueryFactory;
        $this->registry = $registry;
        parent::__construct($catalogConfig,$storeManager,$productVisibility);
    }


    /**
     * Filter product collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param \Magento\Catalog\Model\Category $category
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function filter(
        $collection,
        \Magento\Catalog\Model\Category $category
    ) {
        $collection
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->setStore($this->storeManager->getStore())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addStoreFilter()
            ->addUrlRewrite()
            ->setVisibility($this->productVisibility->getVisibleInSearchIds());

        $query = $this->queryFactory->get();
        $vendorQuery = $this->vendorQueryFactory->get();

        if ($this->isSearchPage()) {
            if (!$vendorQuery->isQueryTextShort()) {
                $collection->addSearchFilter($vendorQuery->getQueryText());
            }
        } else {
            if (!$query->isQueryTextShort()) {
                $collection->addSearchFilter($query->getQueryText());
            }
        }
        
    }

    public function isSearchPage()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\Registry')->registry('search_result_page');
    }
    
}
