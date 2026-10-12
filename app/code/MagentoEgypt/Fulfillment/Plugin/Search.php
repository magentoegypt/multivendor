<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Plugin;
final class Search
{
    public function __construct(private \MagentoEgypt\Fulfillment\Model\BrowseArea $area,
        private \MagentoEgypt\Fulfillment\Model\CatalogEligibility $eligibility,
        private \Magento\Framework\Api\FilterBuilder $filters,
        private \Magento\Framework\Api\Search\FilterGroupBuilder $groups) {}
    public function beforeSearch($subject,$criteria): array
    {
        $area=$this->area->get();
        if (!$area) return [$criteria];
        if (!in_array($criteria->getRequestName(),['quick_search_container','advanced_search_container','catalog_view_container','graphql_product_search','graphql_product_search_with_aggregation'],true)) return [$criteria];
        $eligible=$this->eligibility->ids(...$area);
        $filter=$this->filters->setField('hf_entity_ids')->setConditionType('in')->setValue($eligible ?: [0])->create();
        $criteria=clone $criteria;
        $criteria->setFilterGroups(array_merge($criteria->getFilterGroups(),[$this->groups->setFilters([$filter])->create()]));
        return [$criteria];
    }
}
