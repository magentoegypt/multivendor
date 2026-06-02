<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use MagentoEgypt\OdooConnector\Model\Api\OdooException;
use MagentoEgypt\OdooConnector\Model\Order\OrderPusher;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Orders write-back Magento -> Odoo: create Odoo sale.orders from Magento orders.
 * Create-once + idempotent via the entity map (keyed on increment_id).
 */
class OrdersPushCommand extends Command
{
    private const OPT_LIMIT = 'limit';
    private const OPT_DRY_RUN = 'dry-run';

    private CollectionFactory $orderCollectionFactory;
    private OrderPusher $orderPusher;
    private CorrelationId $correlationId;

    public function __construct(
        CollectionFactory $orderCollectionFactory,
        OrderPusher $orderPusher,
        CorrelationId $correlationId,
        ?string $name = null
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderPusher = $orderPusher;
        $this->correlationId = $correlationId;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('odoo:orders:push')
            ->setDescription('Push Magento orders to Odoo sale.orders (create-once, idempotent via the entity map).')
            ->addOption(self::OPT_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Max orders to push (most recent first)', '20')
            ->addOption(self::OPT_DRY_RUN, null, InputOption::VALUE_NONE, 'Preview only; no Odoo writes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, (int)$input->getOption(self::OPT_LIMIT));
        $dryRun = (bool)$input->getOption(self::OPT_DRY_RUN);
        $correlation = $this->correlationId->generate();

        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToSelect(['entity_id', 'increment_id'])->setOrder('entity_id', 'DESC')->setPageSize($limit);

        $output->writeln(sprintf(
            '<info>Pushing up to %d orders to Odoo%s (correlation %s)</info>',
            $limit,
            $dryRun ? ' [DRY RUN]' : '',
            $correlation
        ));

        $created = 0;
        $exists = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($collection as $order) {
            if ($dryRun) {
                $output->writeln(sprintf('  would push order #%s (id=%s)', $order->getIncrementId(), $order->getEntityId()));
                continue;
            }

            try {
                $result = $this->orderPusher->pushById((int)$order->getEntityId(), $correlation);
                switch ($result['action']) {
                    case 'create':
                        $output->writeln(sprintf('  CREATED #%s -> sale.order %d (%d line(s))', $result['increment_id'], $result['odoo_id'] ?? 0, $result['lines'] ?? 0));
                        $created++;
                        break;
                    case 'exists':
                        $exists++;
                        break;
                    default:
                        $output->writeln(sprintf('  SKIP #%s (%s)', $result['increment_id'], $result['reason'] ?? 'n/a'));
                        $skipped++;
                }
            } catch (OdooException $e) {
                $output->writeln(sprintf('  <error>FAILED order id=%s: %s</error>', $order->getEntityId(), $e->getMessage()));
                $failed++;
            }
        }

        $output->writeln(sprintf('<info>Done.</info> created=%d exists=%d skipped=%d failed=%d', $created, $exists, $skipped, $failed));

        return $failed > 0 ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
    }
}
