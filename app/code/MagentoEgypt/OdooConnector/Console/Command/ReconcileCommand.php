<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Framework\Console\Cli;
use MagentoEgypt\OdooConnector\Model\Reconcile\ReconciliationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReconcileCommand extends Command
{
    private const OPT_LIMIT = 'limit';

    private ReconciliationService $reconciliation;

    public function __construct(ReconciliationService $reconciliation, ?string $name = null)
    {
        $this->reconciliation = $reconciliation;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('odoo:reconcile')
            ->setDescription('Reconcile: re-drive failed queue rows + re-enqueue linked entities to heal drift.')
            ->addOption(self::OPT_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Max linked rows to re-enqueue per domain', '200');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->reconciliation->run(max(1, (int)$input->getOption(self::OPT_LIMIT)));
        $output->writeln(sprintf('<info>Reconcile:</info> failed re-driven=%d', $result['failed_requeued']));
        foreach ($result['requeued'] as $type => $count) {
            $output->writeln(sprintf('  re-enqueued %-22s %d', $type, $count));
        }

        return Cli::RETURN_SUCCESS;
    }
}
