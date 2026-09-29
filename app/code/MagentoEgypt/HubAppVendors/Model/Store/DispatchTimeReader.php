<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use Psr\Log\LoggerInterface;

/**
 * HmDispatchTime for many sellers: declared first, measured for the rest (rules in DispatchTime).
 *
 * The measured figure is HomeSections VendorMeta's query restricted to the
 * sellers asked for: order created -> shipment created over the seller's
 * products. Labels are translated in ONE storefront emulation per call (theme
 * CSVs). Two queries per batch at most.
 */
class DispatchTimeReader
{
    public const ATTRIBUTE = 'dispatch_time';

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly VendorAttributeReader $attributes,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $vendorIds
     * @return array<int, array{code: string, label: string, source: string}> for sellers that have one
     */
    public function forVendors(array $vendorIds, int $storeId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $vendorIds), static fn (int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }

        $plan = [];
        foreach ($this->attributes->values(self::ATTRIBUTE, $ids) as $vendorId => $value) {
            $code = DispatchTime::declaredCode($value);
            if ($code !== null) {
                $plan[$vendorId] = ['code' => $code, 'days' => null, 'source' => DispatchTime::SOURCE_DECLARED];
            }
        }

        $undeclared = array_values(array_diff($ids, array_keys($plan)));
        foreach ($this->measuredDays($undeclared) as $vendorId => $days) {
            $plan[$vendorId] = [
                'code' => DispatchTime::measuredCode($days),
                'days' => $days,
                'source' => DispatchTime::SOURCE_MEASURED,
            ];
        }
        if (!$plan) {
            return [];
        }

        return $this->emulation->run($storeId, static function () use ($plan): array {
            $out = [];
            foreach ($plan as $vendorId => $entry) {
                if ($entry['source'] === DispatchTime::SOURCE_DECLARED) {
                    $label = __(DispatchTime::DECLARED_LABELS[$entry['code']]);
                } elseif ($entry['days'] <= 1) {
                    $label = __(DispatchTime::MEASURED_ONE_DAY);
                } else {
                    $label = __(DispatchTime::MEASURED_DAYS, $entry['days']);
                }
                $out[$vendorId] = [
                    'code' => $entry['code'],
                    'label' => (string) $label,
                    'source' => $entry['source'],
                ];
            }

            return $out;
        });
    }

    /**
     * @param int[] $vendorIds
     * @return array<int, int> vendor id => credible measured days
     */
    private function measuredDays(array $vendorIds): array
    {
        if (!$vendorIds) {
            return [];
        }

        try {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(['pe' => $this->resource->getTableName('catalog_product_entity')], ['vendor_id'])
                    ->join(['oi' => $this->resource->getTableName('sales_order_item')], 'oi.product_id = pe.entity_id', [])
                    ->join(['o' => $this->resource->getTableName('sales_order')], 'o.entity_id = oi.order_id', [])
                    ->join(
                        ['sh' => $this->resource->getTableName('sales_shipment')],
                        'sh.order_id = o.entity_id',
                        [
                            'shipments' => 'COUNT(DISTINCT sh.entity_id)',
                            'avg_hours' => 'AVG(TIMESTAMPDIFF(HOUR, o.created_at, sh.created_at))',
                        ]
                    )
                    ->where('pe.vendor_id IN (?)', $vendorIds)
                    ->group('pe.vendor_id')
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller dispatch times unavailable: ' . $e->getMessage());

            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $days = DispatchTime::measuredDays((int) $row['shipments'], (float) $row['avg_hours']);
            if ($days !== null) {
                $out[(int) $row['vendor_id']] = $days;
            }
        }

        return $out;
    }
}
