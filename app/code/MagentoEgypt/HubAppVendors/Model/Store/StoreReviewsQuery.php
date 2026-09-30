<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Seller\ListableProducts;
use MagentoEgypt\HubApp\Model\Seller\SellerDirectory;
use MagentoEgypt\HubApp\Model\Seller\SellerRatings;

/**
 * One page of a store's Reviews tab (hmStoreReviews).
 *
 * The summary is the seller's rating exactly as the card shows it
 * (SellerRatings); the items are the reviews behind it that the store view
 * shows (SellerReviews), newest first, each with the product it is about
 * (ReviewProducts, whose names and thumbnails need the storefront emulation
 * this builds in).
 */
class StoreReviewsQuery
{
    public function __construct(
        private readonly SellerDirectory $directory,
        private readonly SellerRatings $ratings,
        private readonly SellerReviews $reviews,
        private readonly ReviewProducts $products,
        private readonly ListableProducts $listableProducts,
        private readonly StorefrontEmulationInterface $emulation
    ) {
    }

    /**
     * @return array<string, mixed>|null HmStoreReviewPage plus vendor_entity_id (for the cache
     *         identity); null when $code is not an approved seller
     */
    public function execute(string $code, int $pageSize, int $currentPage, int $storeId): ?array
    {
        $vendor = $this->directory->findApprovedByCode($code);
        if ($vendor === null) {
            return null;
        }
        $vendorId = (int) $vendor['id'];
        $pageSize = max(1, min(SellerReviews::MAX_PAGE_SIZE, $pageSize));
        $currentPage = max(1, $currentPage);

        return $this->emulation->run(
            $storeId,
            fn (): array => $this->build($vendorId, $storeId, $pageSize, $currentPage)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function build(int $vendorId, int $storeId, int $pageSize, int $currentPage): array
    {
        $rating = $this->ratings->forVendors([$vendorId])[$vendorId] ?? ['rating' => null, 'review_count' => 0];
        $page = $this->reviews->page($vendorId, $storeId, $pageSize, $currentPage);
        $rows = $page['rows'];

        $votes = $rows ? $this->reviews->ratings(array_column($rows, 'review_id')) : [];
        $products = $rows
            ? $this->products->forPage(
                array_column($rows, 'product_id'),
                $this->listableProducts->forVendors([$vendorId], $storeId)[$vendorId] ?? [],
                $storeId
            )
            : [];

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => $row['review_id'],
                'nickname' => $row['nickname'],
                'title' => $row['title'] !== '' ? $row['title'] : null,
                'text' => $row['detail'] !== '' ? $row['detail'] : null,
                'rating' => $votes[$row['review_id']] ?? null,
                'created_at' => StoreCards::isoUtc($row['created_at']),
                'product' => $products[$row['product_id']] ?? null,
                //  Not in the schema: the cache identity tags the page's products.
                'product_id' => $row['product_id'],
            ];
        }

        $total = (int) $page['total'];

        return [
            'vendor_entity_id' => $vendorId,
            'summary' => [
                'rating' => $rating['rating'] ?? null,
                'review_count' => (int) ($rating['review_count'] ?? 0),
            ],
            'items' => $items,
            'total_count' => $total,
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'total_pages' => (int) ceil($total / $pageSize),
            ],
        ];
    }
}
