<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Offer;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * The "select and sell" family of each product (Vnecoms_VendorsPriceComparison).
 *
 * A seller who sells a product another seller already lists does not share it:
 * Vnecoms copies it into a product of their own (own SKU, price, stock and
 * vendor_id) whose `select_from_product_id` names the original. The original —
 * the "main product", the one listings show — has none. Its cron can hand the
 * main role to another member (lowest price), re-pointing every copy at it.
 *
 * A family is therefore the main product plus every product pointing at it.
 * The attribute is global (store 0 only), as StorefrontVisibility::searchableIds
 * reads it. Two queries for any number of products; a product that is not in a
 * family is a family of one.
 */
class FamilyReader
{
    public const ATTRIBUTE = 'select_from_product_id';

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly EavConfig $eavConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $productIds
     * @return array<int, int[]> product id => its family (itself included), ascending ids
     */
    public function families(array $productIds): array
    {
        $ids = self::normalise($productIds);
        if (!$ids) {
            return [];
        }

        $attributeId = $this->attributeId();
        $pairs = [];
        if ($attributeId !== null) {
            try {
                $connection = $this->resource->getConnection();
                $table = $this->resource->getTableName('catalog_product_entity_int');
                $base = $connection->select()
                    ->from($table, ['entity_id', 'value'])
                    ->where('attribute_id = ?', $attributeId)
                    ->where('store_id = 0')
                    ->where('value > 0');

                //  Which of the products are copies, and of what …
                foreach ($connection->fetchPairs((clone $base)->where('entity_id IN (?)', $ids)) as $copy => $main) {
                    $pairs[(int) $copy] = (int) $main;
                }
                //  … then every copy of those main products.
                $mains = array_values(array_unique(array_map(
                    static fn (int $id): int => $pairs[$id] ?? $id,
                    $ids
                )));
                foreach ($connection->fetchPairs((clone $base)->where('value IN (?)', $mains)) as $copy => $main) {
                    $pairs[(int) $copy] = (int) $main;
                }
            } catch (\Throwable $e) {
                //  No families means no offers: never list a seller we could not check.
                $this->logger->warning('HubApp: select-and-sell families unavailable: ' . $e->getMessage());
                $pairs = [];
            }
        }

        return self::group($ids, $pairs);
    }

    /**
     * Families from "copy id => main product id" pairs. Pure; unit-tested.
     *
     * @param int[] $productIds
     * @param array<int, int> $copyOf every known copy => the product it was copied from
     * @return array<int, int[]> product id => its family (itself included), ascending ids
     */
    public static function group(array $productIds, array $copyOf): array
    {
        $byMain = [];
        foreach ($copyOf as $copy => $main) {
            $copy = (int) $copy;
            $main = (int) $main;
            if ($copy > 0 && $main > 0 && $copy !== $main) {
                $byMain[$main][$copy] = $copy;
            }
        }

        $out = [];
        foreach (self::normalise($productIds) as $productId) {
            $main = (int) ($copyOf[$productId] ?? $productId);
            if ($main <= 0 || ($main === $productId && !isset($byMain[$main]))) {
                $out[$productId] = [$productId];
                continue;
            }
            $members = [$main => $main] + ($byMain[$main] ?? []);
            $members[$productId] = $productId;
            $members = array_values($members);
            sort($members);
            $out[$productId] = $members;
        }

        return $out;
    }

    /**
     * Positive unique ints, order kept.
     *
     * @param array<int|string, mixed> $ids
     * @return int[]
     */
    public static function normalise(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0 && !in_array($id, $out, true)) {
                $out[] = $id;
            }
        }

        return $out;
    }

    private function attributeId(): ?int
    {
        try {
            $id = (int) $this->eavConfig->getAttribute(Product::ENTITY, self::ATTRIBUTE)->getId();
        } catch (\Throwable $e) {
            return null;
        }

        return $id ?: null;
    }
}
