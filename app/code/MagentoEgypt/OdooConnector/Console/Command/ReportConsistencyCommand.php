<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;
use MagentoEgypt\OdooConnector\Model\Api\OdooException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reports & Analytics domain (acceptance criterion 5): a read-only cross-platform
 * consistency report. Reconciles the entity map against Odoo (are mapped records
 * still present?) and summarises queue + audit-log health. No writes.
 */
class ReportConsistencyCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('odoo:report')
            ->setDescription('Cross-platform consistency report: entity map vs Odoo, queue & sync-log health (read-only).');
    }

    private ResourceConnection $resourceConnection;
    private OdooClient $odooClient;

    public function __construct(
        ResourceConnection $resourceConnection,
        OdooClient $odooClient,
        ?string $name = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->odooClient = $odooClient;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->resourceConnection->getConnection();
        $map = $this->resourceConnection->getTableName('magentoegypt_odoo_entity_map');
        $queue = $this->resourceConnection->getTableName('magentoegypt_odoo_sync_queue');
        $log = $this->resourceConnection->getTableName('magentoegypt_odoo_sync_log');

        $output->writeln('<info>=== Odoo Connector — Consistency Report ===</info>');

        // 1) Entity map by type + status.
        $output->writeln("\n<comment>Entity map (by type / status):</comment>");
        $byType = [];
        foreach ($connection->fetchAll("SELECT entity_type, sync_status, COUNT(*) c FROM {$map} GROUP BY entity_type, sync_status") as $r) {
            $byType[$r['entity_type']][$r['sync_status']] = (int)$r['c'];
        }
        foreach ($byType as $type => $statuses) {
            $parts = [];
            foreach ($statuses as $st => $c) {
                $parts[] = "{$st}={$c}";
            }
            $output->writeln(sprintf('  %-24s %s', $type, implode(' ', $parts)));
        }

        // 2) Odoo presence: are mapped odoo_ids still present in Odoo?
        $output->writeln("\n<comment>Odoo presence (mapped records confirmed in Odoo):</comment>");
        $discrepancies = 0;
        $models = $connection->fetchAll(
            "SELECT entity_type, odoo_model FROM {$map} WHERE odoo_id IS NOT NULL AND odoo_model IS NOT NULL GROUP BY entity_type, odoo_model"
        );
        foreach ($models as $m) {
            $ids = array_map('intval', $connection->fetchCol(
                "SELECT DISTINCT odoo_id FROM {$map} WHERE entity_type = ? AND odoo_model = ? AND odoo_id IS NOT NULL",
                [$m['entity_type'], $m['odoo_model']]
            ));
            $present = 0;
            try {
                foreach (array_chunk($ids, 300) as $chunk) {
                    $present += (int)$this->odooClient->executeKw($m['odoo_model'], 'search_count', [[['id', 'in', $chunk]]]);
                }
            } catch (OdooException $e) {
                $output->writeln(sprintf('  %-24s <error>Odoo error: %s</error>', $m['entity_type'], $e->getMessage()));
                continue;
            }
            $missing = count($ids) - $present;
            $discrepancies += max(0, $missing);
            $output->writeln(sprintf(
                '  %-24s %s: %d/%d present%s',
                $m['entity_type'],
                $m['odoo_model'],
                $present,
                count($ids),
                $missing > 0 ? "  <error>{$missing} MISSING</error>" : ''
            ));
        }

        // 3) Queue + log health.
        $queueByStatus = $connection->fetchPairs("SELECT status, COUNT(*) FROM {$queue} GROUP BY status");
        $logByResult = $connection->fetchPairs("SELECT result, COUNT(*) FROM {$log} GROUP BY result");
        $lastSync = $connection->fetchOne("SELECT MAX(created_at) FROM {$log}");
        $failedQueue = (int)($queueByStatus['failed'] ?? 0);

        $output->writeln("\n<comment>Queue / audit log:</comment>");
        $output->writeln('  queue:     ' . ($queueByStatus ? json_encode($queueByStatus) : '(empty)'));
        $output->writeln('  sync_log:  ' . ($logByResult ? json_encode($logByResult) : '(empty)'));
        $output->writeln('  last sync: ' . ($lastSync ?: 'n/a'));

        // 4) Verdict.
        $output->writeln('');
        if ($discrepancies === 0 && $failedQueue === 0) {
            $output->writeln('<info>VERDICT: consistent — all mapped records present in Odoo, no failed queue items.</info>');
        } else {
            $output->writeln(sprintf(
                '<error>VERDICT: %d missing Odoo record(s), %d failed queue item(s) — investigate.</error>',
                $discrepancies,
                $failedQueue
            ));
        }

        return Cli::RETURN_SUCCESS;
    }
}
