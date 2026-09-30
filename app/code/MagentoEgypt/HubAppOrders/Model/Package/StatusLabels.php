<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Model\Package;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;

/**
 * Order status labels as the storefront prints them, for every status of a response in one query.
 *
 * The website names a status through Sales\Model\Order\StatusLabel in the frontend area: a status
 * that customers must not see is first masked (core etc/di.xml maskStatusesMapping: payment_review
 * reads as processing), then the store view's own label wins (Status::getStoreLabel), else the
 * default label through __() with the storefront's translations (the theme's included, hence the
 * emulation). A vendor order is named the same way (Vnecoms\VendorsSales\Model\Order::getStatusLabel).
 */
class StatusLabels implements ResetAfterRequestInterface
{
    /** What core masks on the storefront (fraud maps to itself). */
    private const FRONTEND_MASK = ['payment_review' => 'processing'];

    /** @var array<int, array<string, string>> store id => status code => label, for the request */
    private array $labels = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StorefrontEmulationInterface $emulation
    ) {
    }

    /**
     * @param string[] $codes status codes
     * @return array<string, string> code => label; an unknown code reads as its humanised self
     */
    public function labels(array $codes, int $storeId): array
    {
        $codes = array_values(array_unique(array_filter(
            array_map(static fn ($code): string => trim((string) $code), $codes),
            static fn (string $code): bool => $code !== ''
        )));
        $known = $this->labels[$storeId] ?? [];
        $missing = array_values(array_diff($codes, array_keys($known)));
        if ($missing) {
            $known += $this->read($missing, $storeId);
            $this->labels[$storeId] = $known;
        }

        return array_intersect_key($known, array_flip($codes));
    }

    /**
     * @param string[] $codes
     * @return array<string, string>
     */
    private function read(array $codes, int $storeId): array
    {
        $shown = [];
        foreach ($codes as $code) {
            $shown[$code] = self::FRONTEND_MASK[$code] ?? $code;
        }
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(['s' => $this->resource->getTableName('sales_order_status')], ['status', 'label'])
                ->joinLeft(
                    ['l' => $this->resource->getTableName('sales_order_status_label')],
                    $connection->quoteInto('l.status = s.status AND l.store_id = ?', $storeId),
                    ['store_label' => 'label']
                )
                ->where('s.status IN (?)', array_values(array_unique($shown)))
        );

        return $this->emulation->run($storeId, static function () use ($rows, $shown): array {
            $byStatus = [];
            foreach ($rows as $row) {
                $storeLabel = trim((string) ($row['store_label'] ?? ''));
                $byStatus[(string) $row['status']] = $storeLabel !== ''
                    ? $storeLabel
                    : (string) __((string) $row['label']);
            }
            $out = [];
            foreach ($shown as $code => $status) {
                $out[$code] = $byStatus[$status] ?? (string) __(ucwords(str_replace('_', ' ', $status)));
            }

            return $out;
        });
    }

    /**
     * Per-request memo only; nothing survives a request in a long-running process.
     */
    public function _resetState(): void
    {
        $this->labels = [];
    }
}
