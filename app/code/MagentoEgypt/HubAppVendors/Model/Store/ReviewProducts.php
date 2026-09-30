<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use Psr\Log\LoggerInterface;

/**
 * The products a page of seller reviews is about (HmStoreReviewProduct).
 *
 * Name, URL key and thumbnail as the store view has them: one product
 * collection for the page, store values over the default ones, and the
 * thumbnail through the storefront's image helper (the theme's
 * product_thumbnail_image, the files its mini cart already uses). The URL key is
 * given only for a product the storefront lists — the caller passes the ids
 * that pass its gate — so the app never links to a product page the website
 * would 404; the name still says what was reviewed.
 */
class ReviewProducts
{
    /** Theme image id of the thumbnail (Magento_Catalog view.xml, type thumbnail). */
    public const IMAGE_ID = 'product_thumbnail_image';

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly MediaUrlInterface $mediaUrl,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $productIds products of the page
     * @param int[] $listableIds products the storefront lists (their URL key is given)
     * @return array<int, array{name: string, url_key: string|null, thumbnail_url: string|null}> product id => product
     */
    public function forPage(array $productIds, array $listableIds, int $storeId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $listable = array_flip(array_map('intval', $listableIds));

        try {
            $collection = $this->collectionFactory->create();
            $collection->setStoreId($storeId)
                ->addAttributeToSelect(['name', 'url_key', 'thumbnail', 'small_image'])
                ->addIdFilter($ids);
            $products = $collection->getItems();
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: reviewed products unavailable: ' . $e->getMessage());

            return [];
        }

        $out = [];
        foreach ($products as $product) {
            $id = (int) $product->getId();
            $name = trim((string) $product->getName());
            if ($id < 1 || $name === '') {
                continue;
            }
            $urlKey = trim((string) $product->getData('url_key'));
            $out[$id] = [
                'name' => $name,
                'url_key' => isset($listable[$id]) && $urlKey !== '' ? $urlKey : null,
                'thumbnail_url' => $this->mediaUrl->productImage($product, self::IMAGE_ID, $storeId),
            ];
        }

        return $out;
    }
}
