<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Customer;

use MagentoEgypt\OdooConnector\Model\Sync\Checksum;

/**
 * Maps an Odoo res.partner record to Magento-side primitives.
 * Natural key = lowercased email; falls back to odoo:<id> when absent
 * (so the row is still unique/idempotent but cannot match a Magento customer).
 * Over JSON-RPC Odoo returns false for empty fields.
 */
class CustomerMapper
{
    public const ODOO_FIELDS = ['id', 'name', 'email', 'phone', 'customer_rank', 'write_date'];

    private Checksum $checksum;

    public function __construct(Checksum $checksum)
    {
        $this->checksum = $checksum;
    }

    /**
     * @param array<string, mixed> $record
     */
    public function getEmail(array $record): ?string
    {
        $email = (isset($record['email']) && $record['email'] !== false)
            ? strtolower(trim((string)$record['email']))
            : '';

        return $email !== '' ? $email : null;
    }

    /**
     * @param array<string, mixed> $record
     */
    public function getNaturalKey(array $record): string
    {
        return $this->getEmail($record) ?? ('odoo:' . $this->getOdooId($record));
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
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public function normalize(array $record): array
    {
        return [
            'email' => $this->getEmail($record),
            'name' => (isset($record['name']) && $record['name'] !== false) ? (string)$record['name'] : '',
            'phone' => (isset($record['phone']) && $record['phone'] !== false) ? (string)$record['phone'] : '',
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
