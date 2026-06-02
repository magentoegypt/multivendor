<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Framework\Console\Cli;
use MagentoEgypt\OdooConnector\Model\Queue\Consumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Drains the Odoo sync queue once (same logic the cron runs). Useful for
 * manual/observed runs without triggering all of Magento's crons.
 */
class QueueRunCommand extends Command
{
    private Consumer $consumer;

    public function __construct(Consumer $consumer, ?string $name = null)
    {
        $this->consumer = $consumer;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('odoo:queue:run')
            ->setDescription('Drain the Odoo sync queue once (claim due rows, dispatch, retry/backoff).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->consumer->run();
        $output->writeln(sprintf(
            '<info>Queue run:</info> claimed=%d done=%d failed=%d skipped=%d',
            $result['claimed'],
            $result['done'],
            $result['failed'],
            $result['skipped']
        ));

        return Cli::RETURN_SUCCESS;
    }
}
