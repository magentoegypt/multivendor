<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Cache;

use Magento\Framework\DataObject\IdentityInterface;

/**
 * The `object` of a clean_cache_by_tags event.
 *
 * Core's FPC and Varnish observers (PageCache FlushCacheByTags,
 * CacheInvalidate InvalidateVarnishObserver) resolve tags through
 * Cache\Tag\Resolver, which reads getIdentities() from any IdentityInterface.
 * So a bare carrier of tags is all it takes to purge arbitrary tags from the
 * HTTP cache without a model to save.
 */
final class TagCarrier implements IdentityInterface
{
    /**
     * @param string[] $tags
     */
    public function __construct(private readonly array $tags)
    {
    }

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        return $this->tags;
    }
}
