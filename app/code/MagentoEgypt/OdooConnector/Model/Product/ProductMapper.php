<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Product;

use MagentoEgypt\OdooConnector\Model\Sync\Checksum;

/**
 * Maps an Odoo product.template record to Magento-side primitives.
 *
 * NOTE: field names follow standard Odoo 19 conventions (default_code = SKU,
 * list_price = sale price). Confirm against the live instance on first connect —
 * stores sometimes relocate the SKU to barcode or a custom field. Over JSON-RPC
 * Odoo returns `false` (not null) for empty values, hence the !== false guards.
 */
class ProductMapper
{
    /** Odoo product.template fields read by the pull. */
    public const ODOO_FIELDS = ['id', 'default_code', 'name', 'list_price', 'barcode', 'type', 'write_date'];

    private Checksum $checksum;

    public function __construct(Checksum $checksum)
    {
        $this->checksum = $checksum;
    }

    /**
     * @param array<string, mixed> $record
     */
    public function getSku(array $record): ?string
    {
        $sku = (isset($record['default_code']) && $record['default_code'] !== false)
            ? trim((string)$record['default_code'])
            : '';

        return $sku !== '' ? $sku : null;
    }

    /**
     * Cross-system key for the map. Prefer the real SKU (default_code); when the
     * Odoo product has none, fall back to a stable Odoo-id key so the row is still
     * unique and idempotent — it just can't be matched to a Magento SKU yet.
     *
     * @param array<string, mixed> $record
     */
    public function getNaturalKey(array $record): string
    {
        return $this->getSku($record) ?? ('odoo:' . $this->getOdooId($record));
    }

    /**
     * @param array<string, mixed> $record
     */
    public function getOdooId(array $record): int
    {
        return (int)($record['id'] ?? 0);
    }

    /**
     * @param array<string, mixed> $record
     */
    public function getWriteDate(array $record): ?string
    {
        return (isset($record['write_date']) && $record['write_date'] !== false)
            ? (string)$record['write_date']
            : null;
    }

    /**
     * Normalized content used for the checksum (echo-suppression / conflict detection).
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public function normalize(array $record): array
    {
        return [
            'sku' => $this->getSku($record),
            'name' => (isset($record['name']) && $record['name'] !== false) ? (string)$record['name'] : '',
            'price' => isset($record['list_price']) ? (float)$record['list_price'] : 0.0,
            'barcode' => (isset($record['barcode']) && $record['barcode'] !== false) ? (string)$record['barcode'] : '',
            'type' => (isset($record['type']) && $record['type'] !== false) ? (string)$record['type'] : '',
        ];
    }

    /**
     * @param array<string, mixed> $record
     */
    public function checksum(array $record): string
    {
        return $this->checksum->hash($this->normalize($record));
    }
}
