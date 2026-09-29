<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Seller;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use Psr\Log\LoggerInterface;

/**
 * Every seller row, read once per request, and their localised display names.
 *
 * ves_vendor_entity holds a few dozen rows on this install (the website's
 * VendorNames and SellerResolver read the whole table for the same reason), so
 * one query answers every "who is seller N" of a response. Names for ALL sellers
 * are built in ONE storefront emulation per store view: the theme CSVs that map
 * an Arabic company to its English name only load there.
 *
 * Approval is Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED = 2 (1 is pending,
 * 3 disabled, 4 expired). Duplicated as a constant, as AlgoliaVendor does, so
 * this class loads without Vnecoms.
 */
class SellerDirectory
{
    public const STATUS_APPROVED = 2;

    /** @var array<int, array{id: int, code: string, company: string, status: int, created_at: string}>|null */
    private ?array $vendors = null;

    /** @var array<string, int>|null lower-cased code => vendor id (approved sellers only) */
    private ?array $approvedByCode = null;

    /** @var array<int, array<int, string>> store id => vendor id => localised name */
    private array $names = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Every seller row by vendor entity id.
     *
     * @return array<int, array{id: int, code: string, company: string, status: int, created_at: string}>
     */
    public function all(): array
    {
        if ($this->vendors !== null) {
            return $this->vendors;
        }
        $this->vendors = [];

        try {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(
                        $this->resource->getTableName('ves_vendor_entity'),
                        ['entity_id', 'vendor_id', 'company', 'status', 'created_at']
                    )
                    ->order('entity_id ASC')
            );
        } catch (\Throwable $e) {
            //  No sellers rather than no response: every seller field is nullable.
            $this->logger->warning('HubApp: seller table unavailable: ' . $e->getMessage());

            return $this->vendors;
        }

        foreach ($rows as $row) {
            $id = (int) ($row['entity_id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $this->vendors[$id] = [
                'id' => $id,
                'code' => trim((string) ($row['vendor_id'] ?? '')),
                'company' => trim((string) ($row['company'] ?? '')),
                'status' => (int) ($row['status'] ?? 0),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }

        return $this->vendors;
    }

    /**
     * An approved seller with a routable code, or null (missing, pending, disabled, expired).
     *
     * @return array{id: int, code: string, company: string, status: int, created_at: string}|null
     */
    public function getApproved(int $vendorId): ?array
    {
        $vendor = $this->all()[$vendorId] ?? null;
        if ($vendor === null || $vendor['status'] !== self::STATUS_APPROVED || $vendor['code'] === '') {
            return null;
        }

        return $vendor;
    }

    /**
     * Ids of every approved seller, ascending.
     *
     * @return int[]
     */
    public function approvedIds(): array
    {
        $out = [];
        foreach ($this->all() as $id => $vendor) {
            if ($vendor['status'] === self::STATUS_APPROVED && $vendor['code'] !== '') {
                $out[] = $id;
            }
        }

        return $out;
    }

    /**
     * The approved seller with this code, compared case-insensitively (the website's /shop/<code>).
     *
     * @return array{id: int, code: string, company: string, status: int, created_at: string}|null
     */
    public function findApprovedByCode(string $code): ?array
    {
        $key = mb_strtolower(trim($code), 'UTF-8');
        if ($key === '') {
            return null;
        }
        $id = $this->approvedCodeMap()[$key] ?? null;

        return $id !== null ? $this->getApproved($id) : null;
    }

    /**
     * Approved seller ids for a list of codes, in the order given; unknown codes are skipped.
     *
     * @param string[] $codes
     * @return int[]
     */
    public function approvedIdsForCodes(array $codes): array
    {
        $map = $this->approvedCodeMap();
        $out = [];
        foreach ($codes as $code) {
            $id = $map[mb_strtolower(trim((string) $code), 'UTF-8')] ?? null;
            if ($id !== null && !in_array($id, $out, true)) {
                $out[] = $id;
            }
        }

        return $out;
    }

    /**
     * Localised display names (SellerName rule, then __() as the storefront of $storeId).
     *
     * @param int[] $vendorIds
     * @return array<int, string> vendor id => name, for the ids that exist
     */
    public function names(array $vendorIds, int $storeId): array
    {
        if (!isset($this->names[$storeId])) {
            $vendors = $this->all();
            $this->names[$storeId] = $vendors
                ? $this->emulation->run($storeId, static function () use ($vendors): array {
                    $out = [];
                    foreach ($vendors as $id => $vendor) {
                        $out[$id] = (string) __(SellerName::source($vendor['company'], $vendor['code']));
                    }

                    return $out;
                })
                : [];
        }

        $out = [];
        foreach ($vendorIds as $vendorId) {
            $vendorId = (int) $vendorId;
            if (isset($this->names[$storeId][$vendorId])) {
                $out[$vendorId] = $this->names[$storeId][$vendorId];
            }
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    private function approvedCodeMap(): array
    {
        if ($this->approvedByCode !== null) {
            return $this->approvedByCode;
        }
        $this->approvedByCode = [];
        foreach ($this->all() as $id => $vendor) {
            if ($vendor['status'] === self::STATUS_APPROVED && $vendor['code'] !== '') {
                //  vendor_id is unique per website; this store has one, so first come keeps it.
                $this->approvedByCode[mb_strtolower($vendor['code'], 'UTF-8')] ??= $id;
            }
        }

        return $this->approvedByCode;
    }
}
