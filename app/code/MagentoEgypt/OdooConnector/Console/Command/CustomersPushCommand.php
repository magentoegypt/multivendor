<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Console\Cli;
use MagentoEgypt\OdooConnector\Model\Api\OdooException;
use MagentoEgypt\OdooConnector\Model\Customer\CustomerPusher;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Customers write-back Magento -> Odoo: create/update res.partner from Magento
 * customers. Idempotent via the entity map (keyed on lowercased email).
 */
class CustomersPushCommand extends Command
{
    private const OPT_LIMIT = 'limit';
    private const OPT_DRY_RUN = 'dry-run';
    private const ENTITY_TYPE = 'customer_buyer';

    private CollectionFactory $customerCollectionFactory;
    private CustomerPusher $customerPusher;
    private MapManager $mapManager;
    private CorrelationId $correlationId;

    public function __construct(
        CollectionFactory $customerCollectionFactory,
        CustomerPusher $customerPusher,
        MapManager $mapManager,
        CorrelationId $correlationId,
        ?string $name = null
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->customerPusher = $customerPusher;
        $this->mapManager = $mapManager;
        $this->correlationId = $correlationId;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('odoo:customers:push')
            ->setDescription('Push Magento customers to Odoo (create/update res.partner; idempotent via the entity map).')
            ->addOption(self::OPT_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Max Magento customers to push', '50')
            ->addOption(self::OPT_DRY_RUN, null, InputOption::VALUE_NONE, 'Preview only; perform no Odoo writes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, (int)$input->getOption(self::OPT_LIMIT));
        $dryRun = (bool)$input->getOption(self::OPT_DRY_RUN);
        $correlation = $this->correlationId->generate();

        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect(['firstname', 'lastname', 'email'])->setPageSize($limit)->setCurPage(1);

        $output->writeln(sprintf(
            '<info>Pushing up to %d Magento customers to Odoo%s (correlation %s)</info>',
            $limit,
            $dryRun ? ' [DRY RUN]' : '',
            $correlation
        ));

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($collection as $customer) {
            $email = strtolower(trim((string)$customer->getEmail()));
            if ($email === '') {
                continue;
            }

            if ($dryRun) {
                $map = $this->mapManager->findByNaturalKey(self::ENTITY_TYPE, $email, 0);
                $mapped = ($map !== null && $map->getData('odoo_id'));
                $output->writeln(sprintf('  %s email=%s magento_id=%s', $mapped ? 'UPDATE' : 'CREATE', $email, $customer->getId()));
                $mapped ? $updated++ : $created++;
                continue;
            }

            try {
                $result = $this->customerPusher->pushById((int)$customer->getId(), $correlation);
                $result['action'] === 'create' ? $created++ : $updated++;
            } catch (OdooException $e) {
                $output->writeln(sprintf('  <error>FAILED email=%s: %s</error>', $email, $e->getMessage()));
                $failed++;
            }
        }

        $output->writeln(sprintf('<info>Done.</info> created=%d updated=%d failed=%d', $created, $updated, $failed));

        return $failed > 0 ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
    }
}
