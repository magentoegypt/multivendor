<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Product;

use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;

/**
 * A ranking, gated: the top N ids of a ranker that the storefront would show.
 *
 * Rankers read SQL rankings that know nothing about approval, sellers or
 * visibility. This asks for three times what is needed, passes the batch
 * through the storefront gate and, when the gate threw out too many, reads
 * the next batch — a few rounds at most, never one query per product.
 */
class RankedIds
{
    private const ROUNDS = 4;

    public function __construct(private readonly ProductListLoaderInterface $loader)
    {
    }

    /**
     * @param callable(int $count, int $offset): int[] $fetch raw ranked ids
     * @return int[] at most $limit gated ids, rank order
     */
    public function top(callable $fetch, int $limit, int $storeId): array
    {
        $rows = $this->topRows(
            static fn (int $count, int $offset): array => array_map(
                static fn ($id): array => ['id' => (int) $id],
                $fetch($count, $offset)
            ),
            $limit,
            $storeId
        );

        return array_map(static fn (array $row): int => $row['id'], $rows);
    }

    /**
     * Same, for rankers that return rows with an 'id' key (the deals keep their end dates).
     *
     * @param callable(int $count, int $offset): array<int, array<string, mixed>> $fetch
     * @return array<int, array<string, mixed>>
     */
    public function topRows(callable $fetch, int $limit, int $storeId): array
    {
        if ($limit < 1) {
            return [];
        }

        $out = [];
        $seen = [];
        $offset = 0;
        $batch = max($limit * 3, 12);

        for ($round = 0; $round < self::ROUNDS && count($out) < $limit; $round++) {
            $rows = $fetch($batch, $offset);
            if (!$rows) {
                break;
            }
            $offset += count($rows);

            $byId = [];
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0 && !isset($seen[$id]) && !isset($byId[$id])) {
                    $byId[$id] = $row;
                }
            }
            foreach ($this->loader->sellable(array_keys($byId), $storeId) as $id) {
                if (!isset($seen[$id]) && isset($byId[$id])) {
                    $seen[$id] = true;
                    $out[] = $byId[$id];
                }
            }

            if (count($rows) < $batch) {
                break;   // the ranking is exhausted
            }
        }

        return array_slice($out, 0, $limit);
    }
}
