<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Values of a Vnecoms seller EAV attribute (is_home, dispatch_time, …) for many sellers at once.
 *
 * The attribute is looked up by code WITHIN the seller entity type ("vendor"):
 * attribute codes are unique per entity type only, so an unscoped lookup could
 * pick a product or customer attribute of the same name (the bug HomeSections
 * NewStores documents). Seller EAV values have no store scope
 * (ves_vendor_entity_<type> has no store_id). One meta query per attribute and
 * one value query per batch; results are kept for the request.
 *
 * A missing attribute (e.g. is_home was never created) reads as "no values".
 */
class VendorAttributeReader
{
    private const ENTITY_TYPE = 'vendor';

    private const VALUE_TYPES = ['int', 'varchar', 'text', 'decimal', 'datetime'];

    /** @var array<string, array{id: int, backend: string}|null> */
    private array $attributes = [];

    /** @var array<string, array<int, string|null>> attribute code => vendor id => value */
    private array $values = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $vendorIds
     * @return array<int, string> vendor id => value, for sellers that have one
     */
    public function values(string $code, array $vendorIds): array
    {
        $ids = [];
        foreach ($vendorIds as $vendorId) {
            $vendorId = (int) $vendorId;
            if ($vendorId > 0) {
                $ids[$vendorId] = $vendorId;
            }
        }
        if (!$ids) {
            return [];
        }

        $missing = array_values(array_diff_key($ids, $this->values[$code] ?? []));
        if ($missing) {
            $this->load($code, $missing);
        }

        $out = [];
        foreach ($ids as $vendorId) {
            $value = $this->values[$code][$vendorId] ?? null;
            if ($value !== null) {
                $out[$vendorId] = $value;
            }
        }

        return $out;
    }

    /**
     * @param int[] $vendorIds
     */
    private function load(string $code, array $vendorIds): void
    {
        foreach ($vendorIds as $vendorId) {
            $this->values[$code][$vendorId] = null;
        }
        $attribute = $this->attribute($code);
        if ($attribute === null) {
            return;
        }

        try {
            $connection = $this->resource->getConnection();
            if ($attribute['backend'] === 'static') {
                $table = $this->resource->getTableName('ves_vendor_entity');
                if (!$connection->tableColumnExists($table, $code)) {
                    return;
                }
                $rows = $connection->fetchPairs(
                    $connection->select()
                        ->from($table, ['entity_id', $code])
                        ->where('entity_id IN (?)', $vendorIds)
                );
            } else {
                $rows = $connection->fetchPairs(
                    $connection->select()
                        ->from(
                            $this->resource->getTableName('ves_vendor_entity_' . $attribute['backend']),
                            ['entity_id', 'value']
                        )
                        ->where('attribute_id = ?', $attribute['id'])
                        ->where('entity_id IN (?)', $vendorIds)
                );
            }
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf('HubApp: seller attribute "%s" unavailable: %s', $code, $e->getMessage()));

            return;
        }

        foreach ($rows as $vendorId => $value) {
            $this->values[$code][(int) $vendorId] = $value !== null ? (string) $value : null;
        }
    }

    /**
     * @return array{id: int, backend: string}|null
     */
    private function attribute(string $code): ?array
    {
        if (array_key_exists($code, $this->attributes)) {
            return $this->attributes[$code];
        }
        $this->attributes[$code] = null;

        try {
            $connection = $this->resource->getConnection();
            $row = $connection->fetchRow(
                $connection->select()
                    ->from(['a' => $this->resource->getTableName('eav_attribute')], ['attribute_id', 'backend_type'])
                    ->join(
                        ['t' => $this->resource->getTableName('eav_entity_type')],
                        't.entity_type_id = a.entity_type_id',
                        []
                    )
                    ->where('t.entity_type_code = ?', self::ENTITY_TYPE)
                    ->where('a.attribute_code = ?', $code)
                    ->limit(1)
            );
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf('HubApp: seller attribute "%s" lookup failed: %s', $code, $e->getMessage()));

            return null;
        }

        $backend = is_array($row) ? (string) ($row['backend_type'] ?? '') : '';
        if ($backend !== 'static' && !in_array($backend, self::VALUE_TYPES, true)) {
            return null;
        }

        return $this->attributes[$code] = ['id' => (int) $row['attribute_id'], 'backend' => $backend];
    }
}
