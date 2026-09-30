<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Brand;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use Psr\Log\LoggerInterface;

/**
 * MGS brands of a store view as HmBrand values (hmBrands, TOP_BRANDS).
 *
 * The same rows the website's Top Brands row reads (HomeSections TopBrands:
 * direct SQL on mgs_brand / mgs_brand_store, enabled, store 0 or the store
 * view, admin sort order), with the name through __() under storefront
 * emulation: the theme's en_US.csv is what turns "ابل" into "Apple" on the
 * English store, exactly as top-brands.phtml does.
 *
 * Direct SQL on purpose, like the block: MGS_Brand is a retired vendor module
 * and its tables are the stable part. Missing tables give no brands.
 */
class BrandReader
{
    public const MAX = 200;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly MediaUrlInterface $mediaUrl,
        private readonly LinkResolverInterface $links,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly AppCache $appCache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * One page of brands.
     *
     * @param int[]|null $onlyOptions keep only brands whose option id is listed (null: all)
     * @return array{items: array<int, array<string, mixed>>, total_count: int}
     */
    public function page(int $storeId, bool $featuredOnly, int $pageSize, int $currentPage, ?array $onlyOptions = null): array
    {
        $brands = $this->getBrands($storeId);
        if ($featuredOnly) {
            $brands = array_values(array_filter($brands, static fn (array $b): bool => (bool) $b['is_featured']));
        }
        if ($onlyOptions !== null) {
            $keep = array_flip(array_map('intval', $onlyOptions));
            $brands = array_values(array_filter($brands, static fn (array $b): bool => isset($keep[(int) $b['option_id']])));
        }

        return [
            'items' => array_slice($brands, ($currentPage - 1) * $pageSize, $pageSize),
            'total_count' => count($brands),
        ];
    }

    /**
     * Every enabled brand of the store view, admin order, at most MAX.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBrands(int $storeId): array
    {
        $key = 'brands_' . $storeId;
        $cached = $this->appCache->load($key);
        if ($cached !== null) {
            return $cached;
        }

        $rows = $this->rows($storeId);
        $brands = $rows ? $this->emulation->run($storeId, fn (): array => $this->map($rows, $storeId)) : [];
        $this->appCache->save($key, $brands, [Tags::BRAND], AppCache::MAX_TTL);

        return $brands;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(int $storeId): array
    {
        try {
            $connection = $this->resource->getConnection();
            $brand = $this->resource->getTableName('mgs_brand');
            if (!$connection->isTableExists($brand)) {
                return [];
            }
            $select = $connection->select()
                ->from(['b' => $brand], ['brand_id', 'name', 'url_key', 'small_image', 'image', 'is_featured', 'option_id'])
                ->where('b.status = ?', 1)
                ->order('b.sort_order ASC')
                ->order('b.name ASC')
                ->limit(self::MAX);

            $store = $this->resource->getTableName('mgs_brand_store');
            if ($connection->isTableExists($store)) {
                $select->join(['s' => $store], 's.brand_id = b.brand_id', [])
                    ->where('s.store_id IN (?)', [0, $storeId])
                    ->group('b.brand_id');
            }

            return $connection->fetchAll($select);
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: brands unavailable: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function map(array $rows, int $storeId): array
    {
        $out = [];
        foreach ($rows as $row) {
            $urlKey = trim((string) $row['url_key']);
            $name = trim((string) $row['name']);
            if ($urlKey === '' || $name === '') {
                continue;   // cannot be named or opened
            }
            $small = trim((string) $row['small_image']);
            $large = trim((string) $row['image']);
            $out[] = [
                'id' => (int) $row['brand_id'],
                'option_id' => (int) $row['option_id'],
                //  Theme i18n, as the website's template does (QA cycle 2 item 1-I).
                'name' => (string) __($name),
                'url_key' => $urlKey,
                'logo_url' => $this->mediaUrl->media($small !== '' ? $small : $large, $storeId),
                'image_url' => $this->mediaUrl->media($large, $storeId),
                'is_featured' => (bool) (int) $row['is_featured'],
                'link' => $this->links->brand($urlKey, $storeId),
            ];
        }

        return $out;
    }
}
