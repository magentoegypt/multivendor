<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Cache;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Array get/set on the `hubapp` cache type, honouring its enabled state.
 *
 * TTLs are short (at most MAX_TTL) and tags are filtered through
 * Tags::forAppCache() so a product save by the Odoo sync never empties it
 * (see Tags). A cache failure is a miss, never an error.
 */
class AppCache
{
    /** Upper bound for any entry, seconds. */
    public const MAX_TTL = 900;

    private const KEY_PREFIX = 'HM_APP_';

    public function __construct(
        private readonly Type $cache,
        private readonly StateInterface $cacheState,
        private readonly Json $json,
        private readonly LoggerInterface $logger
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->cacheState->isEnabled(Type::TYPE_IDENTIFIER);
    }

    /**
     * @return array<mixed>|null null on a miss
     */
    public function load(string $key): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }
        try {
            $raw = $this->cache->load(self::KEY_PREFIX . $key);
            if (!is_string($raw) || $raw === '') {
                return null;
            }
            $data = $this->json->unserialize($raw);
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: app cache read failed for ' . $key . ': ' . $e->getMessage());

            return null;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<mixed> $data
     * @param string[] $tags
     */
    public function save(string $key, array $data, array $tags, int $ttl): void
    {
        if (!$this->isEnabled() || $ttl < 1) {
            return;
        }
        try {
            $this->cache->save(
                (string) $this->json->serialize($data),
                self::KEY_PREFIX . $key,
                Tags::forAppCache($tags),
                min($ttl, self::MAX_TTL)
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: app cache write failed for ' . $key . ': ' . $e->getMessage());
        }
    }
}
