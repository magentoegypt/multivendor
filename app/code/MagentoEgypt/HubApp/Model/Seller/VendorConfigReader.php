<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Seller;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Seller settings from ves_vendor_config (what the seller panel writes), batched.
 *
 * Mirrors Vnecoms\VendorsConfig\Helper\Data::getVendorConfig(): rows of store 0
 * and of the store view are read and the store view's row wins. That helper
 * also falls back to vendor_config.xml defaults; none of this install's modules
 * declare a default for the paths below, so the table is the whole answer.
 *
 * One query for any number of sellers and paths; values are kept for the rest
 * of the request.
 */
class VendorConfigReader
{
    /** Logo file, relative to media/ves_vendors/logo (the website's store cards read it too). */
    public const LOGO = 'general/store_information/logo';
    public const SHORT_DESCRIPTION = 'general/store_information/short_description';
    /** Store page banner, relative to media/ves_vendors/banner. */
    public const BANNER = 'page/general/banner';
    public const ABOUT = 'page/general/description';
    public const SHIPPING_POLICY = 'page/general/shipping_policy';
    public const REFUND_POLICY = 'page/general/refund_policy';

    /** @var array<int, array<int, array<string, string|null>>> store id => vendor id => path => value (null = unset) */
    private array $values = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $vendorIds
     * @param string[] $paths
     * @return array<int, array<string, string>> vendor id => path => value; unset paths are absent
     */
    public function read(array $vendorIds, array $paths, int $storeId): array
    {
        $ids = [];
        foreach ($vendorIds as $vendorId) {
            $vendorId = (int) $vendorId;
            if ($vendorId > 0) {
                $ids[$vendorId] = $vendorId;
            }
        }
        $paths = array_values(array_unique(array_filter(array_map('strval', $paths))));
        if (!$ids || !$paths) {
            return [];
        }

        $missingIds = [];
        $missingPaths = [];
        foreach ($ids as $vendorId) {
            foreach ($paths as $path) {
                if (!array_key_exists($path, $this->values[$storeId][$vendorId] ?? [])) {
                    $missingIds[$vendorId] = $vendorId;
                    $missingPaths[$path] = $path;
                }
            }
        }
        if ($missingIds) {
            $this->load(array_values($missingIds), array_values($missingPaths), $storeId);
        }

        $out = [];
        foreach ($ids as $vendorId) {
            foreach ($paths as $path) {
                $value = $this->values[$storeId][$vendorId][$path] ?? null;
                if ($value !== null) {
                    $out[$vendorId][$path] = $value;
                }
            }
        }

        return $out;
    }

    /**
     * @param int[] $vendorIds
     * @param string[] $paths
     */
    private function load(array $vendorIds, array $paths, int $storeId): void
    {
        foreach ($vendorIds as $vendorId) {
            foreach ($paths as $path) {
                $this->values[$storeId][$vendorId][$path] ??= null;
            }
        }

        try {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(
                        $this->resource->getTableName('ves_vendor_config'),
                        ['vendor_id', 'path', 'value', 'store_id']
                    )
                    ->where('vendor_id IN (?)', $vendorIds)
                    ->where('path IN (?)', $paths)
                    ->where('store_id IN (?)', array_values(array_unique([0, $storeId])))
                    //  Store 0 first, so the store view's row overwrites it; within one store the
                    //  oldest row is applied last, as getVendorConfig()'s getFirstItem() picks it.
                    ->order(['store_id ASC', 'config_id DESC'])
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller settings unavailable: ' . $e->getMessage());

            return;
        }

        foreach ($rows as $row) {
            $this->values[$storeId][(int) $row['vendor_id']][(string) $row['path']] = (string) ($row['value'] ?? '');
        }
    }
}
