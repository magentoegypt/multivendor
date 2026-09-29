<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Api;

/**
 * Purges everything the app may have cached under the given tags.
 *
 * Two layers, one call:
 *   1. the `hubapp` cache type (built Home payloads, ranked id lists, seller stats);
 *   2. the full-page cache — built-in FPC or Varnish, whichever is configured —
 *      by dispatching `clean_cache_by_tags`, which is what serves GraphQL GETs.
 *
 * Use the constants in Model\Cache\Tags. Unknown tags are harmless.
 */
interface CacheTagCleanerInterface
{
    /**
     * @param string[] $tags
     */
    public function clean(array $tags): void;
}
