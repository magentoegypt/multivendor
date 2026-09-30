<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HomeSections\Model\Ranking\DealRanker;
use MagentoEgypt\HubApp\Model\Product\DealFacts;
use MagentoEgypt\HubApp\Model\Product\DealFilter;
use MagentoEgypt\HubApp\Model\Product\RankedLists;

/**
 * hmDeals(category_id, min_discount_percent, sort): live special prices of the
 * store view, deepest discount first unless sorted otherwise, paged.
 *
 * Filters and sorts work on the day's whole gated ranking (RankedLists::deals,
 * at most 200), never on one page, so a sort or a filter is exact within it.
 * The department chips count the deals matching every filter but category_id,
 * so they stay while one is selected. The countdown is the soonest end among
 * the offers on THIS page. Items are loaded by HmProductPage.items
 * (ProductPageItems) from the page's ids.
 */
class Deals implements ResolverInterface
{
    public function __construct(
        private readonly RankedLists $lists,
        private readonly DealRanker $dealRanker,
        private readonly DealFacts $dealFacts,
        private readonly Uid $uid
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        [$pageSize, $currentPage] = Paging::args($args, 20, 50);
        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
        $categoryId = isset($args['category_id']) && (int) $args['category_id'] > 0 ? (int) $args['category_id'] : null;
        $minPercent = isset($args['min_discount_percent']) ? (int) $args['min_discount_percent'] : null;
        if ($minPercent !== null && ($minPercent < 0 || $minPercent > 100)) {
            throw new GraphQlInputException(__('min_discount_percent must be between 0 and 100.'));
        }
        $sort = isset($args['sort']) ? strtoupper((string) $args['sort']) : DealFilter::DISCOUNT;

        $rows = $this->lists->deals($storeId);
        $wantsChips = isset($info->getFieldSelection(1)['categories']);
        $facts = [];
        $names = [];
        if ($rows && ($categoryId !== null || $wantsChips || !in_array($sort, [DealFilter::DISCOUNT, DealFilter::ENDING_SOON], true))) {
            $read = $this->dealFacts->forProducts(
                array_map('intval', array_column($rows, 'id')),
                $storeId,
                $this->dealRanker->today($storeId)
            );
            $facts = $read['products'];
            $names = $read['names'];
        }

        //  Every filter but the category: what the department chips count.
        $offered = DealFilter::filter($rows, $facts, null, $minPercent);
        $matching = DealFilter::sort(DealFilter::filter($offered, $facts, $categoryId, null), $facts, $sort);
        $page = Paging::slice($matching, $pageSize, $currentPage);

        return [
            'total_count' => count($matching),
            'page_info' => Paging::info(count($matching), $pageSize, $currentPage),
            'countdown_ends_at' => $this->dealRanker->countdown($page, $storeId),
            'categories' => $wantsChips ? $this->chips(DealFilter::departments($offered, $facts, $names)) : null,
            ProductPageItems::IDS_KEY => array_map('intval', array_column($page, 'id')),
        ];
    }

    /**
     * @param array<int, array{id: int, name: string, count: int}> $departments
     * @return array<int, array<string, mixed>> HmCategoryCount values
     */
    private function chips(array $departments): array
    {
        return array_map(fn (array $chip): array => [
            'id' => $chip['id'],
            'uid' => $this->uid->encode((string) $chip['id']),
            'name' => $chip['name'],
            'count' => $chip['count'],
        ], $departments);
    }
}
