<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * HTTP cache tag of hmAppConfig. A response is only cached when some field
 * returns a tag (GraphQlCache CacheableQueryHandler), so this is what makes
 * the launch call cacheable at all.
 */
class AppConfigIdentity implements IdentityInterface
{
    /**
     * @param array<mixed> $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        return [Tags::APP_CONFIG];
    }
}
