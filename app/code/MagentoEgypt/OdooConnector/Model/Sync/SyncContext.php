<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Sync;

/**
 * Process-wide flag, true while an inbound (Odoo->Magento) apply is writing to
 * Magento. The outbound observers/plugins consult it (via QueueManager) and skip
 * re-enqueuing that write — the belt to the checksum/context suspenders, so an
 * inbound apply never bounces straight back out as an M->O job. DI singleton.
 */
class SyncContext
{
    private bool $inbound = false;

    public function setInbound(bool $inbound): void
    {
        $this->inbound = $inbound;
    }

    public function isInbound(): bool
    {
        return $this->inbound;
    }
}
