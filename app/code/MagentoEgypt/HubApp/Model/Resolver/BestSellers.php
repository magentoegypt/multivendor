<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubApp\Model\Product\RankedLists;

/**
 * hmBestSellers: products by units ordered (top-level lines, cancelled orders
 * excluded), gated like the storefront, paged.
 */
class BestSellers implements ResolverInterface
{
    public function __construct(private readonly RankedLists $lists)
    {
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

        $ids = $this->lists->bestSellers($storeId);

        return [
            'total_count' => count($ids),
            'page_info' => Paging::info(count($ids), $pageSize, $currentPage),
            'countdown_ends_at' => null,
            ProductPageItems::IDS_KEY => Paging::slice($ids, $pageSize, $currentPage),
        ];
    }
}
