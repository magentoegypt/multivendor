<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HomeSections\Model\BundleDeal\BundleDealBuilder;
use MagentoEgypt\HubApp\Model\Catalog\BundleCards;

/**
 * hmBundleDeals(category_id): the bundles page — cards newest first, the
 * department chips (from ALL bundles, so they stay while one is selected) and
 * the stats of the matching bundles.
 */
class BundleDeals implements ResolverInterface
{
    /** Where the bundle ids of the page go, for BundleDealIdentity. */
    public const IDS_KEY = '_ids';

    public function __construct(private readonly BundleCards $bundleCards)
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
        $store = $context->getExtensionAttributes()->getStore();
        $categoryId = isset($args['category_id']) && (int) $args['category_id'] > 0 ? (int) $args['category_id'] : null;

        $cards = $this->bundleCards->get((int) $store->getId());
        $matching = BundleCards::inCategory($cards['items'], $categoryId);
        $page = Paging::slice($matching, $pageSize, $currentPage);

        return [
            'items' => $this->bundleCards->toGraphQl($page, $store),
            'categories' => $this->bundleCards->categoryCounts($cards['items'], $cards['category_names']),
            'max_discount_percent' => BundleDealBuilder::maxDiscount($matching),
            'seller_count' => BundleDealBuilder::sellerCount($matching),
            'total_count' => count($matching),
            'page_info' => Paging::info(count($matching), $pageSize, $currentPage),
            self::IDS_KEY => BundleCards::ids($page),
        ];
    }
}
