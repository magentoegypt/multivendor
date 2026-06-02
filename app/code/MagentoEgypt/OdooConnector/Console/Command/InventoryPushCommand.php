<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use MagentoEgypt\OdooConnector\Model\Api\OdooException;
use MagentoEgypt\OdooConnector\Model\Inventory\InventoryPusher;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Inventory write-back Magento -> Odoo: pushes MSI on-hand for SKUs that are
 * already mapped to an Odoo product (joins inventory_source_item to the product
 * entity map, so it never touches stock for products that don't exist in Odoo).
 */
class InventoryPushCommand extends Command
{
    private const OPT_LIMIT = 'limit';
    private const OPT_DRY_RUN = 'dry-run';

    private ResourceConnection $resourceConnection;
    private InventoryPusher $inventoryPusher;
    private CorrelationId $correlationId;

    public function __construct(
        ResourceConnection $resourceConnection,
        InventoryPusher $inventoryPusher,
        CorrelationId $correlationId,
        ?string $name = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->inventoryPusher = $inventoryPusher;
        $this->correlationId = $correlationId;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('odoo:inventory:push')
            ->setDescription('Push MSI on-hand to Odoo for SKUs already mapped to an Odoo product (idempotent).')
            ->addOption(self::OPT_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Max source items to push', '500')
            ->addOption(self::OPT_DRY_RUN, null, InputOption::VALUE_NONE, 'Preview only; no Odoo writes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, (int)$input->getOption(self::OPT_LIMIT));
        $dryRun = (bool)$input->getOption(self::OPT_DRY_RUN);
        $correlation = $this->correlationId->generate();

        $connection = $this->resourceConnection->getConnection();
        $sourceItem = $this->resourceConnection->getTableName('inventory_source_item');
        $map = $this->resourceConnection->getTableName('magentoegypt_odoo_entity_map');

        // Only source items whose SKU is a product already mapped to Odoo.
        $rows = $connection->fetchAll(
            "SELECT si.source_code, si.sku, si.quantity FROM {$sourceItem} si "
            . "INNER JOIN {$map} m ON m.entity_type = 'product' AND m.magento_natural_key = si.sku AND m.odoo_id IS NOT NULL "
            . 'ORDER BY si.sku LIMIT ' . (int)$limit
        );

        $output->writeln(sprintf(
            '<info>Pushing on-hand for %d mapped source item(s)%s (correlation %s)</info>',
            count($rows),
            $dryRun ? ' [DRY RUN]' : '',
            $correlation
        ));

        $set = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $sku = (string)$row['sku'];
            $sourceCode = (string)$row['source_code'];
            $qty = (float)$row['quantity'];

            if ($dryRun) {
                $output->writeln(sprintf('  SET %s:%s -> qty=%s', $sourceCode, $sku, $qty));
                $set++;
                continue;
            }

            try {
                $result = $this->inventoryPusher->push($sku, $sourceCode, $qty, $correlation);
                if ($result['action'] === 'set') {
                    $output->writeln(sprintf('  SET %s:%s -> qty=%s (odoo product %d)', $sourceCode, $sku, $qty, $result['odoo_product_id'] ?? 0));
                    $set++;
                } else {
                    $output->writeln(sprintf('  SKIP %s:%s (%s)', $sourceCode, $sku, $result['reason'] ?? 'n/a'));
                    $skipped++;
                }
            } catch (OdooException $e) {
                $output->writeln(sprintf('  <error>FAILED %s:%s: %s</error>', $sourceCode, $sku, $e->getMessage()));
                $failed++;
            }
        }

        $output->writeln(sprintf('<info>Done.</info> set=%d skipped=%d failed=%d', $set, $skipped, $failed));

        return $failed > 0 ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
    }
}
