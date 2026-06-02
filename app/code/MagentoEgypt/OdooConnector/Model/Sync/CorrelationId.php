<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Sync;

/**
 * Generates RFC-4122 v4 UUIDs used to thread one logical change through
 * queue -> HTTP -> log -> response (the audit correlation id).
 */
class CorrelationId
{
    public function generate(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant 10xx

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
