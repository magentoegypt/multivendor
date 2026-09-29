<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use Psr\Log\LoggerInterface;

/**
 * HmDispatchTime for many sellers: declared first, measured for the rest (rules in DispatchTime).
 *
 * The measured figure is HomeSections VendorMeta's query: order created ->
 * shipment created over the seller's products. It joins sales_order_item on
 * product_id, which has no index (Vnecoms' sales_order_item.vendor_id has none
 * either, and holds the seller at order time rather than the product's current
 * one, so it would change the figures), i.e. it reads the whole order-item
 * table. So it runs for EVERY seller at once, grouped by seller exactly as
 * before, and the result is kept in the `hubapp` app cache for
 * AppCache::MAX_TTL, tagged hm_vendor (seller saves and the catalogue cron
 * purge it), like SellerRatings. Labels are translated in ONE storefront
 * emulation per call (theme CSVs).
 */
class DispatchTimeReader implements ResetAfterRequestInterface
{
    public const ATTRIBUTE = 'dispatch_time';

    private const CACHE_KEY = 'seller_dispatch_measured';

    /** @var array<int, int>|null vendor id => credible measured days, every seller */
    private ?array $measured = null;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly VendorAttributeReader $attributes,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly AppCache $appCache,
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
        $all = $this->allMeasuredDays();
        $out = [];
        foreach ($vendorIds as $vendorId) {
            if (isset($all[$vendorId])) {
                $out[$vendorId] = $all[$vendorId];
            }
        }

        return $out;
    }

    /**
     * @return array<int, int> vendor id => credible measured days, for every seller that has one
     */
    private function allMeasuredDays(): array
    {
        if ($this->measured !== null) {
            return $this->measured;
        }

        $cached = $this->appCache->load(self::CACHE_KEY);
        if ($cached !== null) {
            $this->measured = [];
            foreach ($cached as $vendorId => $days) {
                $this->measured[(int) $vendorId] = (int) $days;
            }

            return $this->measured;
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
                    ->where('pe.vendor_id > ?', 0)
                    ->group('pe.vendor_id')
            );
        } catch (\Throwable $e) {
            //  Not cached: the next request tries again.
            $this->logger->warning('HubApp: seller dispatch times unavailable: ' . $e->getMessage());

            return $this->measured = [];
        }

        $out = [];
        foreach ($rows as $row) {
            $days = DispatchTime::measuredDays((int) $row['shipments'], (float) $row['avg_hours']);
            if ($days !== null) {
                $out[(int) $row['vendor_id']] = $days;
            }
        }
        $this->appCache->save(self::CACHE_KEY, $out, [Tags::VENDOR], AppCache::MAX_TTL);

        return $this->measured = $out;
    }

    /**
     * Per-request memo only; the shared copy lives in the app cache.
     */
    public function _resetState(): void
    {
        $this->measured = null;
    }
}
