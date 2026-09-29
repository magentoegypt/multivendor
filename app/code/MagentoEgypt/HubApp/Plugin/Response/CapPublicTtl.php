<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Plugin\Response;

use Magento\Framework\App\Response\Http;
use MagentoEgypt\HubApp\Model\Cache\ResponseTtl;

/**
 * Before Http::setPublicHeaders($ttl), GraphQL area only: apply the request's
 * ResponseTtl ceiling. GraphQlCache calls setPublicHeaders() with the store's
 * full-page-cache TTL on every cacheable GET; nothing else in the graphql area
 * does. With no ceiling set the TTL passes through unchanged.
 */
class CapPublicTtl
{
    public function __construct(private readonly ResponseTtl $responseTtl)
    {
    }

    /**
     * @param Http $subject
     * @param int|string $ttl
     * @return array{0: int|string}
     */
    public function beforeSetPublicHeaders(Http $subject, $ttl): array
    {
        if ($this->responseTtl->getCap() === null) {
            return [$ttl];
        }

        //  A string, like the config value it replaces: setPublicHeaders() runs preg_match on it.
        return [(string) $this->responseTtl->apply((int) $ttl)];
    }
}
