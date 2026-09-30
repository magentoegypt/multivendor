<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * Cache type `hubapp` (System > Cache Management: "Hub Market App").
 *
 * Holds what the app GraphQL can rebuild but should not rebuild per request:
 * built Home payloads per (store, audience), ranked product id lists, bundle
 * cards, brand lists. Never product data, never anything per customer.
 * Disabling the type makes every request rebuild; nothing breaks.
 */
class Type extends TagScope
{
    public const TYPE_IDENTIFIER = 'hubapp';

    public const CACHE_TAG = 'HUBAPP';

    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }
}
