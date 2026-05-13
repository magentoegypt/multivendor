<?php
/**
 * Copyright © Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsProductGraphQl\Model\Plugin\ProductSearch;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;


/**
 * Class TypePlugin
 */
class ProductCollectionSearchCriteriaBuilder
{
    const VENDOR_STATUS_FIELD = ["vendor_status", "vendor_id_filter", "approval_status"];


    /** @var FilterBuilder */
    private $filterBuilder;

    /** @var FilterGroupBuilder */
    private $filterGroupBuilder;

    /**
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     */
    public function __construct(
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
    }

    /**
     * @param \Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch\ProductCollectionSearchCriteriaBuilder $subject
     * @param $result
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function afterBuild(
        \Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch\ProductCollectionSearchCriteriaBuilder $subject,
        $result,
        SearchCriteriaInterface $searchCriteria
    ) {

        $groups = $result->getFilterGroups();
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if (in_array($filter->getField(), self::VENDOR_STATUS_FIELD)) {
                    $vendorFilter = $this->filterBuilder
                        ->setField($filter->getField())
                        ->setValue($filter->getValue())
                        ->setConditionType($filter->getConditionType())
                        ->create();

                    $this->filterGroupBuilder->addFilter($vendorFilter);
                    $groups[] = $this->filterGroupBuilder->create();
                }
            }
        }
        $result->setFilterGroups($groups);
        return $result;
    }
}
