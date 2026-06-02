<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;
use MagentoEgypt\OdooConnector\Model\Api\OdooException;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;
use MagentoEgypt\OdooConnector\Model\Product\ProductMapper;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Phase 2 (Products, read-only Odoo -> Magento): reads products from Odoo and
 * records them in the entity map, proving the dedup keystone. It writes ONLY
 * the map table — it does not create/modify Magento catalog products. Running
 * it twice must yield the same map rows (one per SKU), never duplicates.
 */
class ProductsPullCommand extends Command
{
    private const OPT_LIMIT = 'limit';
    private const OPT_DRY_RUN = 'dry-run';
    private const ENTITY_TYPE = 'product';
    private const ODOO_MODEL = 'product.template';

    private OdooClient $odooClient;
    private ProductMapper $mapper;
    private MapManager $mapManager;
    private ProductRepositoryInterface $productRepository;
    private CorrelationId $correlationId;

    public function __construct(
        OdooClient $odooClient,
        ProductMapper $mapper,
        MapManager $mapManager,
        ProductRepositoryInterface $productRepository,
        CorrelationId $correlationId,
        ?string $name = null
    ) {
        $this->odooClient = $odooClient;
        $this->mapper = $mapper;
        $this->mapManager = $mapManager;
        $this->productRepository = $productRepository;
        $this->correlationId = $correlationId;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('odoo:products:pull')
            ->setDescription('Read products from Odoo and link them in the entity map (read-only Odoo->Magento; writes only the map table).')
            ->addOption(self::OPT_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Max products to read from Odoo', '50')
            ->addOption(self::OPT_DRY_RUN, null, InputOption::VALUE_NONE, 'Preview only; do not write the entity map');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, (int)$input->getOption(self::OPT_LIMIT));
        $dryRun = (bool)$input->getOption(self::OPT_DRY_RUN);
        $correlation = $this->correlationId->generate();

        $output->writeln(sprintf(
            '<info>Pulling up to %d Odoo products%s (correlation %s)</info>',
            $limit,
            $dryRun ? ' [DRY RUN]' : '',
            $correlation
        ));

        try {
            $records = $this->odooClient->executeKw(
                self::ODOO_MODEL,
                'search_read',
                [[]],
                ['fields' => ProductMapper::ODOO_FIELDS, 'limit' => $limit, 'order' => 'id']
            );
        } catch (OdooException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Cli::RETURN_FAILURE;
        }

        if (!is_array($records)) {
            $output->writeln('<error>Unexpected Odoo response (expected a list of records).</error>');

            return Cli::RETURN_FAILURE;
        }

        $linked = 0;
        $pending = 0;
        $echoed = 0;
        $noSku = 0;

        foreach ($records as $record) {
            $naturalKey = $this->mapper->getNaturalKey($record);
            $sku = $this->mapper->getSku($record);
            if ($sku === null) {
                $noSku++;
            }
            $incomingChecksum = $this->mapper->checksum($record);
            $existing = $this->mapManager->findByNaturalKey(self::ENTITY_TYPE, $naturalKey, 0);

            // Echo-suppression: unchanged since last sync, or an echo of our own write.
            if ($existing !== null && $this->mapManager->isEcho($existing, $incomingChecksum, 'odoo')) {
                $echoed++;
                if ($dryRun) {
                    $output->writeln(sprintf('  ECHO key=%s odoo_id=%d (unchanged)', $naturalKey, $this->mapper->getOdooId($record)));
                }
                continue;
            }

            $magentoId = $sku !== null ? $this->resolveMagentoId($sku) : null;

            if ($dryRun) {
                $output->writeln(sprintf(
                    '  %s key=%s odoo_id=%d magento=%s',
                    $magentoId !== null ? 'LINK' : 'PEND',
                    $naturalKey,
                    $this->mapper->getOdooId($record),
                    $magentoId ?? '(none)'
                ));
                $magentoId !== null ? $linked++ : $pending++;
                continue;
            }

            $data = [
                'entity_type' => self::ENTITY_TYPE,
                'magento_natural_key' => $naturalKey,
                'odoo_model' => self::ODOO_MODEL,
                'odoo_id' => $this->mapper->getOdooId($record),
                'odoo_checksum' => $incomingChecksum,
                'odoo_write_date' => $this->mapper->getWriteDate($record),
                'last_direction' => EntityMap::DIRECTION_O2M,
                'website_id' => 0,
                'last_correlation_id' => $correlation,
            ];

            if ($magentoId !== null) {
                $data['magento_id'] = $magentoId;
                $data['sync_status'] = EntityMap::STATUS_LINKED;
                $linked++;
            } else {
                $data['sync_status'] = EntityMap::STATUS_PENDING;
                $pending++;
            }

            $this->mapManager->link($data);
        }

        $output->writeln(sprintf(
            '<info>Done.</info> read=%d linked=%d pending=%d echoed(skipped)=%d (no Odoo SKU=%d)',
            count($records),
            $linked,
            $pending,
            $echoed,
            $noSku
        ));

        return Cli::RETURN_SUCCESS;
    }

    private function resolveMagentoId(string $sku): ?string
    {
        try {
            return (string)$this->productRepository->get($sku)->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
