<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Cache;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * A per-request ceiling on how long the HTTP cache may keep this response.
 *
 * Magento gives every cacheable GraphQL GET the same lifetime
 * (system/full_page_cache/ttl, a day by default). A response that carries
 * something that expires — the Algolia key of hmAppConfig, valid 24 hours —
 * must not be served from cache for that long. A resolver calls cap();
 * Plugin\Response\CapPublicTtl lowers the lifetime GraphQlCache writes into
 * Cache-Control (max-age and s-maxage), which is what both the built-in FPC
 * (Kernel::process) and Varnish (beresp.ttl) store the entry for.
 */
class ResponseTtl implements ResetAfterRequestInterface
{
    private ?int $cap = null;

    /**
     * Keep the response in the HTTP cache for at most $seconds (the lowest cap wins).
     */
    public function cap(int $seconds): void
    {
        $seconds = max(1, $seconds);
        $this->cap = $this->cap === null ? $seconds : min($this->cap, $seconds);
    }

    public function apply(int $ttl): int
    {
        return $this->cap === null ? $ttl : min($ttl, $this->cap);
    }

    public function getCap(): ?int
    {
        return $this->cap;
    }

    public function _resetState(): void
    {
        $this->cap = null;
    }
}
