<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Cache;

use Magento\Framework\Event\ManagerInterface as EventManager;
use MagentoEgypt\HubApp\Api\CacheTagCleanerInterface;
use Psr\Log\LoggerInterface;

/**
 * Purges tags from the `hubapp` cache type and from the HTTP cache.
 *
 * The HTTP half goes through the clean_cache_by_tags event rather than calling
 * FPC or Varnish directly, so it follows whichever cache the store runs
 * (built-in or Varnish) exactly like a core model save does.
 *
 * Never throws: a failed purge is logged. A save in admin must not fail
 * because a cache backend is briefly unreachable.
 */
class TagCleaner implements CacheTagCleanerInterface
{
    public function __construct(
        private readonly Type $cache,
        private readonly EventManager $eventManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function clean(array $tags): void
    {
        $tags = array_values(array_unique(array_filter(
            array_map(static fn ($tag): string => trim((string) $tag), $tags),
            static fn (string $tag): bool => $tag !== ''
        )));
        if (!$tags) {
            return;
        }

        try {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: app cache purge failed: ' . $e->getMessage());
        }

        try {
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => new TagCarrier($tags)]);
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: HTTP cache purge failed: ' . $e->getMessage());
        }
    }
}
