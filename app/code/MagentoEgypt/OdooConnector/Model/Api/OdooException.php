<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Raised for any Odoo external-API transport or protocol failure.
 */
class OdooException extends LocalizedException
{
    /**
     * True when Odoo reported the target record is gone — e.g. a stale entity-map
     * odoo_id after a version migration reassigned/dropped record IDs, or a manual
     * delete. Callers use this to drop the stale link and re-attach by natural key
     * (SKU/email/increment_id) instead of failing the sync.
     */
    public function isMissingRecord(): bool
    {
        $message = (string)$this->getMessage();

        return stripos($message, 'does not exist') !== false
            || stripos($message, 'has been deleted') !== false;
    }
}
