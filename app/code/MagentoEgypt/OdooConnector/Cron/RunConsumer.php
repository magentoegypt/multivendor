<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Cron;

use MagentoEgypt\OdooConnector\Model\Queue\Consumer;

/**
 * Cron entry point: drains the sync queue each tick.
 */
class RunConsumer
{
    private Consumer $consumer;

    public function __construct(Consumer $consumer)
    {
        $this->consumer = $consumer;
    }

    public function execute(): void
    {
        $this->consumer->run();
    }
}
