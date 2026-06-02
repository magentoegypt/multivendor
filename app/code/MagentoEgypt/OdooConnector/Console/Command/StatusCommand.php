<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Read-only snapshot of the connector's three tables. Safe to run anytime.
 */
class StatusCommand extends Command
{
    private const TABLES = [
        'magentoegypt_odoo_entity_map' => 'Entity map',
        'magentoegypt_odoo_sync_queue' => 'Sync queue',
        'magentoegypt_odoo_sync_log' => 'Sync log',
    ];

    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection, ?string $name = null)
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('odoo:status')
            ->setDescription('Show Odoo connector mapping/queue/log counts (read-only).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->resourceConnection->getConnection();

        $output->writeln('<info>Odoo Connector status</info>');
        foreach (self::TABLES as $logical => $label) {
            $table = $this->resourceConnection->getTableName($logical);
            $count = (int)$connection->fetchOne("SELECT COUNT(*) FROM {$table}");
            $output->writeln(sprintf('  %-12s %6d rows  (%s)', $label, $count, $table));
        }

        $queueTable = $this->resourceConnection->getTableName('magentoegypt_odoo_sync_queue');
        $byStatus = $connection->fetchPairs("SELECT status, COUNT(*) FROM {$queueTable} GROUP BY status");
        if ($byStatus) {
            $output->writeln('  queue by status: ' . json_encode($byStatus));
        }

        return Cli::RETURN_SUCCESS;
    }
}
