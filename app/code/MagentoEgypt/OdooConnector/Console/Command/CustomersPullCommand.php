<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;
use MagentoEgypt\OdooConnector\Model\Api\OdooException;
use MagentoEgypt\OdooConnector\Model\Customer\CustomerMapper;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Customers read-only Odoo -> Magento: reads Odoo customers (res.partner with
 * customer_rank>0), keys them by email, and links them in the entity map.
 * Writes only the map table; echo-aware (skips unchanged rows).
 */
class CustomersPullCommand extends Command
{
    private const OPT_LIMIT = 'limit';
    private const OPT_DRY_RUN = 'dry-run';
    private const ENTITY_TYPE = 'customer_buyer';
    private const ODOO_MODEL = 'res.partner';

    private OdooClient $odooClient;
    private CustomerMapper $mapper;
    private MapManager $mapManager;
    private CorrelationId $correlationId;
    private ResourceConnection $resourceConnection;

    public function __construct(
        OdooClient $odooClient,
        CustomerMapper $mapper,
        MapManager $mapManager,
        CorrelationId $correlationId,
        ResourceConnection $resourceConnection,
        ?string $name = null
    ) {
        $this->odooClient = $odooClient;
        $this->mapper = $mapper;
        $this->mapManager = $mapManager;
        $this->correlationId = $correlationId;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('odoo:customers:pull')
            ->setDescription('Read Odoo customers (res.partner, customer_rank>0) and link them in the entity map (read-only; writes only the map table).')
            ->addOption(self::OPT_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Max customers to read from Odoo', '500')
            ->addOption(self::OPT_DRY_RUN, null, InputOption::VALUE_NONE, 'Preview only; do not write the entity map');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, (int)$input->getOption(self::OPT_LIMIT));
        $dryRun = (bool)$input->getOption(self::OPT_DRY_RUN);
        $correlation = $this->correlationId->generate();

        $output->writeln(sprintf(
            '<info>Pulling up to %d Odoo customers%s (correlation %s)</info>',
            $limit,
            $dryRun ? ' [DRY RUN]' : '',
            $correlation
        ));

        try {
            $records = $this->odooClient->executeKw(
                self::ODOO_MODEL,
                'search_read',
                [[['customer_rank', '>', 0]]],
                ['fields' => CustomerMapper::ODOO_FIELDS, 'limit' => $limit, 'order' => 'id']
            );
        } catch (OdooException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Cli::RETURN_FAILURE;
        }

        if (!is_array($records)) {
            $output->writeln('<error>Unexpected Odoo response.</error>');

            return Cli::RETURN_FAILURE;
        }

        $linked = 0;
        $pending = 0;
        $echoed = 0;
        $noEmail = 0;

        foreach ($records as $record) {
            $naturalKey = $this->mapper->getNaturalKey($record);
            $email = $this->mapper->getEmail($record);
            if ($email === null) {
                $noEmail++;
            }
            $incomingChecksum = $this->mapper->checksum($record);
            $existing = $this->mapManager->findByNaturalKey(self::ENTITY_TYPE, $naturalKey, 0);

            if ($existing !== null && $this->mapManager->isEcho($existing, $incomingChecksum, 'odoo')) {
                $echoed++;
                continue;
            }

            $magentoId = $email !== null ? $this->resolveMagentoCustomerId($email) : null;

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
            '<info>Done.</info> read=%d linked=%d pending=%d echoed(skipped)=%d (no email=%d)',
            count($records),
            $linked,
            $pending,
            $echoed,
            $noEmail
        ));

        return Cli::RETURN_SUCCESS;
    }

    private function resolveMagentoCustomerId(string $email): ?string
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('customer_entity');
        $id = $connection->fetchOne(
            "SELECT entity_id FROM {$table} WHERE email = ? ORDER BY entity_id ASC LIMIT 1",
            [$email]
        );

        return $id ? (string)$id : null;
    }
}
