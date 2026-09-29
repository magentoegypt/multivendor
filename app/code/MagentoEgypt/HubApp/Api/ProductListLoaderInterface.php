<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Api;

use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * Loads ranked product ids as GraphQL ProductInterface values, in rank order.
 *
 * Every product the app is handed passes ONE gate first — the storefront's:
 * approved by the marketplace, owned by an active seller, enabled and
 * catalog-visible in the store (VendorExtend StorefrontVisibility::sellableIds),
 * and not a Vnecoms "select and sell" copy (::searchableIds). That is the same
 * gate the website's rails, the search index and the seller pages use, so the
 * app can never link to a product the website would 404.
 */
interface ProductListLoaderInterface
{
    /**
     * The subset of $productIds the storefront would show on $storeId, rank order kept.
     *
     * One or two queries for the whole list. Fails open (returns the input) if the
     * marketplace tables are missing, like the website's gate.
     *
     * @param int[] $productIds
     * @return int[]
     */
    public function sellable(array $productIds, int $storeId): array;

    /**
     * Product values for ProductInterface fields, in the order of $productIds.
     *
     * Ids that are gated out, missing or not in the store are dropped. Each
     * value is $product->getData() plus 'model' => $product, which is what
     * core's product field resolvers expect.
     *
     * @param int[] $productIds ranked ids
     * @param string[] $fields requested ProductInterface fields (ProductFieldsSelector)
     * @return array<int, array<string, mixed>> list in rank order
     */
    public function load(array $productIds, array $fields, ContextInterface $context): array;
}
