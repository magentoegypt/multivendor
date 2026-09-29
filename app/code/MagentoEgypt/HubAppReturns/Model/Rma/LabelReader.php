<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;

/**
 * Localised labels for returns: RMA statuses and reasons (per store view, as the website picks them)
 * and order statuses. One query per table per request, whatever the number of rows shown.
 *
 * Status and reason labels live in ves_rma_status_store / ves_rma_reason_store, whose store_id is a
 * varchar. The website (Status::getLabelByStoreId, Reason::getLabelByStoreId) shows the store view's
 * row, else the default title, else "N/A"; a blank store label also falls back here. On Hub Market
 * the default reason titles are Arabic and the English wording is in the "en" store view's rows.
 */
class LabelReader
{
    /** @var array<int, array<int, array{code: string, label: string}>> store id => status id => row */
    private array $statuses = [];

    /** @var array<int, array<int, array{id: int, label: string, active: bool}>> store id => reason id => row */
    private array $reasons = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StorefrontEmulationInterface $emulation
    ) {
    }

    /**
     * Every RMA status: code and label for the store view.
     *
     * @return array<int, array{code: string, label: string}>
     */
    public function statuses(int $storeId): array
    {
        if (!isset($this->statuses[$storeId])) {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()->from(
                    $this->resource->getTableName('ves_rma_status'),
                    ['status_id', 'code', 'title']
                )
            );
            $labels = $this->storeLabels('ves_rma_status_store', 'status_id', $storeId);
            $out = [];
            foreach ($rows as $row) {
                $id = (int) $row['status_id'];
                $out[$id] = [
                    'code' => (string) $row['code'],
                    'label' => $this->pick($labels[$id] ?? null, (string) $row['title'], (string) $row['code']),
                ];
            }
            $this->statuses[$storeId] = $out;
        }

        return $this->statuses[$storeId];
    }

    /**
     * The status id of a status code (the website files new returns as "pending", status 1).
     */
    public function statusIdByCode(string $code, int $fallback): int
    {
        foreach ($this->statuses(0) as $id => $status) {
            if ($status['code'] === $code) {
                return $id;
            }
        }

        return $fallback;
    }

    /**
     * Every reason in the website's order (sort_order, then id), with its store label and whether
     * admin has it enabled (status 1; Vnecoms never filtered on it, the hub-market template does).
     *
     * @return array<int, array{id: int, label: string, active: bool}>
     */
    public function reasons(int $storeId): array
    {
        if (!isset($this->reasons[$storeId])) {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from($this->resource->getTableName('ves_rma_reason'), ['reason_id', 'title', 'status'])
                    ->order(['sort_order ASC', 'reason_id ASC'])
            );
            $labels = $this->storeLabels('ves_rma_reason_store', 'reason_id', $storeId);
            $out = [];
            foreach ($rows as $row) {
                $id = (int) $row['reason_id'];
                $out[$id] = [
                    'id' => $id,
                    'label' => $this->pick($labels[$id] ?? null, (string) $row['title'], 'N/A'),
                    'active' => (int) $row['status'] === 1,
                ];
            }
            $this->reasons[$storeId] = $out;
        }

        return $this->reasons[$storeId];
    }

    /**
     * Order status labels as the storefront prints them (Sales\Model\Order\Status::getStoreLabel):
     * the store view's label, else the default label through __() with the theme's translations.
     *
     * @param string[] $statusCodes
     * @return array<string, string> status code => label
     */
    public function orderStatusLabels(array $statusCodes, int $storeId): array
    {
        $statusCodes = array_values(array_unique(array_filter(array_map('strval', $statusCodes))));
        if (!$statusCodes) {
            return [];
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
                ->where('s.status IN (?)', $statusCodes)
        );

        return $this->emulation->run($storeId, static function () use ($rows, $statusCodes): array {
            $out = [];
            foreach ($rows as $row) {
                $storeLabel = trim((string) $row['store_label']);
                $out[(string) $row['status']] = $storeLabel !== '' ? $storeLabel : (string) __((string) $row['label']);
            }
            foreach ($statusCodes as $code) {
                $out[$code] ??= (string) __(ucwords(str_replace('_', ' ', $code)));
            }

            return $out;
        });
    }

    /**
     * @return array<int, string> entity id => label of this store view
     */
    private function storeLabels(string $table, string $idColumn, int $storeId): array
    {
        if ($storeId <= 0) {
            return [];
        }
        $connection = $this->resource->getConnection();

        return array_map('strval', $connection->fetchPairs(
            $connection->select()
                ->from($this->resource->getTableName($table), [$idColumn, 'title'])
                ->where('TRIM(store_id) = ?', (string) $storeId)
        ));
    }

    private function pick(?string $storeLabel, string $title, string $last): string
    {
        foreach ([$storeLabel, $title, $last] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'N/A';
    }
}
