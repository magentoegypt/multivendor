<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Synchronized reporting across Magento and Odoo: presents the key business
 * metrics from BOTH systems side by side so reporting stays reconciled.
 * Covers Sales, Revenue, Orders, Inventory, Customers, Product Performance.
 * Promotion & Commission reports are deferred (out of v1 scope).
 */
class ReportAnalyticsCommand extends Command
{
    private ResourceConnection $resource;
    private OdooClient $odooClient;

    public function __construct(ResourceConnection $resource, OdooClient $odooClient, ?string $name = null)
    {
        $this->resource = $resource;
        $this->odooClient = $odooClient;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('odoo:report:analytics')
            ->setDescription('Synchronized reporting (Magento vs Odoo): sales, revenue, orders, inventory, customers, product performance.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->resource->getConnection();
        $map = $this->resource->getTableName('magentoegypt_odoo_entity_map');

        $orderOdooIds = array_map('intval', $conn->fetchCol("SELECT odoo_id FROM {$map} WHERE entity_type='order' AND odoo_id IS NOT NULL"));
        $custOdooIds = array_map('intval', $conn->fetchCol("SELECT DISTINCT odoo_id FROM {$map} WHERE entity_type='customer_buyer' AND odoo_id IS NOT NULL"));
        $prodOdooIds = array_map('intval', $conn->fetchCol("SELECT odoo_id FROM {$map} WHERE entity_type='product' AND magento_id IS NOT NULL AND odoo_id IS NOT NULL"));

        $output->writeln('<info>=== Synchronized Analytics — Magento (baw_org) vs Odoo ===</info>');

        // [1] SALES & REVENUE
        $m = $conn->fetchRow("SELECT COUNT(*) c, COALESCE(SUM(grand_total),0) rev FROM " . $this->resource->getTableName('sales_order'));
        $oCount = 0;
        $oRev = 0.0;
        foreach ($this->readOdoo('sale.order', $orderOdooIds, ['amount_total']) as $r) {
            $oCount++;
            $oRev += (float)($r['amount_total'] ?? 0);
        }
        $output->writeln("\n<comment>[1] SALES & REVENUE</comment>");
        $output->writeln(sprintf('    Magento : %d orders, revenue %.2f', (int)$m['c'], (float)$m['rev']));
        $output->writeln(sprintf('    Odoo    : %d mapped sale.orders, revenue %.2f', $oCount, $oRev));

        // [2] ORDERS BY STATUS
        $output->writeln("\n<comment>[2] ORDERS BY STATUS</comment>");
        $output->writeln('    Magento:');
        foreach ($conn->fetchAll("SELECT status, COUNT(*) c FROM " . $this->resource->getTableName('sales_order') . " GROUP BY status ORDER BY c DESC") as $r) {
            $output->writeln(sprintf('      %-16s %d', (string)$r['status'], (int)$r['c']));
        }
        $byState = [];
        foreach ($this->readOdoo('sale.order', $orderOdooIds, ['state']) as $r) {
            $s = (string)($r['state'] ?? '?');
            $byState[$s] = ($byState[$s] ?? 0) + 1;
        }
        $output->writeln('    Odoo (mapped):');
        foreach ($byState as $s => $c) {
            $output->writeln(sprintf('      %-16s %d', $s, $c));
        }

        // [3] INVENTORY
        $mInv = (float)$conn->fetchOne(
            "SELECT COALESCE(SUM(isi.quantity),0) FROM " . $this->resource->getTableName('inventory_source_item') . " isi"
            . " INNER JOIN " . $this->resource->getTableName('catalog_product_entity') . " cpe ON cpe.sku = isi.sku"
            . " INNER JOIN {$map} m ON m.entity_type='product' AND m.magento_id = cpe.entity_id"
        );
        $oInv = 0.0;
        foreach ($this->readOdoo('product.template', $prodOdooIds, ['qty_available']) as $r) {
            $oInv += (float)($r['qty_available'] ?? 0);
        }
        $output->writeln("\n<comment>[3] INVENTORY (mapped products, on-hand)</comment>");
        $output->writeln(sprintf('    Magento MSI sum: %.2f', $mInv));
        $output->writeln(sprintf('    Odoo on-hand sum: %.2f', $oInv));

        // [4] CUSTOMERS
        $cTotal = (int)$conn->fetchOne("SELECT COUNT(*) FROM " . $this->resource->getTableName('customer_entity'));
        $cMapped = (int)$conn->fetchOne("SELECT COUNT(DISTINCT magento_id) FROM {$map} WHERE entity_type='customer_buyer' AND magento_id IS NOT NULL");
        $output->writeln("\n<comment>[4] CUSTOMERS</comment>");
        $output->writeln(sprintf('    Magento : %d total, %d synced to Odoo', $cTotal, $cMapped));
        $output->writeln(sprintf('    Odoo    : %d mapped res.partner', count($custOdooIds)));

        // [5] PRODUCT PERFORMANCE
        $output->writeln("\n<comment>[5] PRODUCT PERFORMANCE (top 5 by qty ordered)</comment>");
        $rows = $conn->fetchAll(
            "SELECT sku, SUM(qty_ordered) q, SUM(row_total) rev FROM " . $this->resource->getTableName('sales_order_item')
            . " WHERE parent_item_id IS NULL GROUP BY sku ORDER BY q DESC LIMIT 5"
        );
        if ($rows === []) {
            $output->writeln('    (no order items)');
        }
        foreach ($rows as $r) {
            $output->writeln(sprintf('      %-32s qty=%.0f revenue=%.2f', (string)$r['sku'], (float)$r['q'], (float)$r['rev']));
        }

        // [6] PROMOTION & COMMISSION
        $output->writeln("\n<comment>[6] PROMOTION & COMMISSION PERFORMANCE</comment>");
        $output->writeln('    (deferred per scope — enable the promotions/commissions phase to populate)');

        $output->writeln("\n<info>Done.</info>");

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param int[] $ids
     * @param string[] $fields
     * @return array<int, array<string, mixed>>
     */
    private function readOdoo(string $model, array $ids, array $fields): array
    {
        if ($ids === []) {
            return [];
        }
        $rows = $this->odooClient->executeKw($model, 'read', [$ids], ['fields' => $fields]);

        return is_array($rows) ? $rows : [];
    }
}
