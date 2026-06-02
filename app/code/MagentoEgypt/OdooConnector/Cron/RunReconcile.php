<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Cron;

use MagentoEgypt\OdooConnector\Model\Reconcile\ReconciliationService;

/**
 * Cron entry point for periodic reconciliation (re-drive failures + heal drift).
 */
class RunReconcile
{
    private ReconciliationService $reconciliation;

    public function __construct(ReconciliationService $reconciliation)
    {
        $this->reconciliation = $reconciliation;
    }

    public function execute(): void
    {
        $this->reconciliation->run();
    }
}
