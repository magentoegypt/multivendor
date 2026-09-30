<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Brand;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use Psr\Log\LoggerInterface;

/**
 * How many products each brand's page lists, and from how many sellers
 * (HmBrand.product_count, HmBrand.seller_count, hmBrands(with_products)).
 *
 * A brand's products are the products whose `mgs_brand` value is the brand's
 * option (the store view's value over the default one), the attribute MGS's
 * brand page and the app's brand page (products mgs_brand eq option_id) filter
 * on. They are counted after the storefront's one visibility gate,
 * ProductListLoaderInterface::sellable() (approved, active seller, enabled and
 * catalog-visible in the store view, not a "select and sell" copy, in stock
 * when the store hides the rest), the seller cards' rule, so a brand never
 * promises more products than its page shows. Sellers are the distinct
 * vendor_id of those products; vendor 0, Hub Market itself, counts as one, as
 * on the bundle page.
 *
 * Computed for every brand of a store view at once (one attribute query, the
 * gate, one owner query) and kept in the `hubapp` cache under hm_brand and
 * hm_app_catalog (the cron purges it when the catalogue moves).
 */
class BrandCounts implements ResetAfterRequestInterface
{
    public const ATTRIBUTE = 'mgs_brand';

    /** @var array<int, array<int, array{products: int, sellers: int}>> store id => option id => counts */
    private array $byStore = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly EavConfig $eavConfig,
        private readonly ProductListLoaderInterface $productListLoader,
        private readonly AppCache $appCache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{products: int, sellers: int}
     */
    public function forOption(int $optionId, int $storeId): array
    {
        return $this->forStore($storeId)[$optionId] ?? ['products' => 0, 'sellers' => 0];
    }

    /**
     * @return array<int, array{products: int, sellers: int}> option id => counts, brands with products only
     */
    public function forStore(int $storeId): array
    {
        if (isset($this->byStore[$storeId])) {
            return $this->byStore[$storeId];
        }

        $key = 'brand_counts_' . $storeId;
        $cached = $this->appCache->load($key);
        if ($cached !== null) {
            $out = [];
            foreach ($cached as $optionId => $counts) {
                if (is_array($counts)) {
                    $out[(int) $optionId] = [
                        'products' => (int) ($counts['products'] ?? 0),
                        'sellers' => (int) ($counts['sellers'] ?? 0),
                    ];
                }
            }

            return $this->byStore[$storeId] = $out;
        }

        $counts = $this->read($storeId);
        if ($counts !== null) {
            $this->appCache->save($key, $counts, [Tags::BRAND, Tags::APP_CATALOG], AppCache::MAX_TTL);
        }

        return $this->byStore[$storeId] = $counts ?? [];
    }

    /**
     * Per option: the listed products and their distinct sellers. Pure: unit-tested.
     *
     * @param array<int, int[]> $optionsByProduct product id => its brand option ids
     * @param int[] $listed product ids the storefront gate passes
     * @param array<int, int> $vendorByProduct product id => vendor id (0 = Hub Market)
     * @return array<int, array{products: int, sellers: int}>
     */
    public static function tally(array $optionsByProduct, array $listed, array $vendorByProduct): array
    {
        $products = [];
        $sellers = [];
        foreach ($listed as $productId) {
            $productId = (int) $productId;
            foreach (array_unique($optionsByProduct[$productId] ?? []) as $optionId) {
                $products[$optionId] = ($products[$optionId] ?? 0) + 1;
                $sellers[$optionId][(int) ($vendorByProduct[$productId] ?? 0)] = true;
            }
        }

        $out = [];
        foreach ($products as $optionId => $count) {
            $out[(int) $optionId] = ['products' => $count, 'sellers' => count($sellers[$optionId])];
        }
        ksort($out);

        return $out;
    }

    /**
     * Store-view values over default ones; a multiselect value is a comma list. Pure: unit-tested.
     *
     * @param array<int, array{entity_id: int|string, store_id: int|string, value: mixed}> $rows
     * @return array<int, int[]> product id => option ids
     */
    public static function optionsByProduct(array $rows, int $storeId): array
    {
        $values = [];
        foreach ($rows as $row) {
            $productId = (int) $row['entity_id'];
            $rowStore = (int) $row['store_id'];
            if ($rowStore !== 0 && $rowStore !== $storeId) {
                continue;   // another store view's value
            }
            $isStore = $rowStore !== 0;
            if (!$isStore && isset($values[$productId]) && $values[$productId]['store']) {
                continue;
            }
            $values[$productId] = ['store' => $isStore, 'value' => (string) $row['value']];
        }

        $out = [];
        foreach ($values as $productId => $value) {
            $ids = array_values(array_unique(array_filter(
                array_map('intval', explode(',', $value['value'])),
                static fn (int $id): bool => $id > 0
            )));
            if ($ids) {
                $out[$productId] = $ids;
            }
        }

        return $out;
    }

    /**
     * @return array<int, array{products: int, sellers: int}>|null null when the brands' products could not be read
     */
    private function read(int $storeId): ?array
    {
        try {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, self::ATTRIBUTE);
            $attributeId = (int) $attribute->getId();
            if (!$attributeId) {
                return [];
            }
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from($attribute->getBackendTable(), ['entity_id', 'store_id', 'value'])
                    ->where('attribute_id = ?', $attributeId)
                    ->where('store_id IN (?)', array_values(array_unique([0, $storeId])))
                    ->where('value IS NOT NULL')
            );
            $optionsByProduct = self::optionsByProduct($rows, $storeId);
            if (!$optionsByProduct) {
                return [];
            }

            $listed = $this->productListLoader->sellable(array_keys($optionsByProduct), $storeId);
            $vendors = [];
            if ($listed) {
                foreach ($connection->fetchPairs(
                    $connection->select()
                        ->from($this->resource->getTableName('catalog_product_entity'), ['entity_id', 'vendor_id'])
                        ->where('entity_id IN (?)', $listed)
                ) as $productId => $vendorId) {
                    $vendors[(int) $productId] = (int) $vendorId;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: brand counts unavailable: ' . $e->getMessage());

            return null;
        }

        return self::tally($optionsByProduct, $listed, $vendors);
    }

    public function _resetState(): void
    {
        $this->byStore = [];
    }
}
