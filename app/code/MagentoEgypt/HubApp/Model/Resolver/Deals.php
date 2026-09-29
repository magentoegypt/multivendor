<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HomeSections\Model\Ranking\DealRanker;
use MagentoEgypt\HubApp\Model\Product\RankedLists;

/**
 * hmDeals: live special prices of the store view, deepest discount first, paged.
 *
 * The countdown is the soonest end among the offers on THIS page. Items are
 * loaded by HmProductPage.items (ProductPageItems) from the page's ids.
 */
class Deals implements ResolverInterface
{
    public function __construct(
        private readonly RankedLists $lists,
        private readonly DealRanker $dealRanker
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

        $rows = $this->lists->deals($storeId);
        $page = Paging::slice($rows, $pageSize, $currentPage);

        return [
            'total_count' => count($rows),
            'page_info' => Paging::info(count($rows), $pageSize, $currentPage),
            'countdown_ends_at' => $this->dealRanker->countdown($page, $storeId),
            ProductPageItems::IDS_KEY => array_map('intval', array_column($page, 'id')),
        ];
    }
}
