<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQlCache\Model\CacheableQuery;
use MagentoEgypt\HubApp\Model\Home\HomeBuilder;
use Psr\Log\LoggerInterface;

/**
 * hmAppHome(audience): the app Home of the store view in the Store header.
 *
 * Public and shared by every viewer of the store view and audience: send it as
 * GET without Authorization so the HTTP cache serves it (HomeIdentity tags).
 * Products of the product sections are loaded per request by
 * HmHomeSection.products (one collection for the whole Home).
 *
 * A section that fails is dropped by the builder (and that response is kept
 * briefly); if the Home as a whole cannot be built the error reaches the app,
 * which then keeps its built-in CMS-driven Home — an empty Home would be cached
 * and look intentional. The failed response is marked uncacheable, so no other
 * field of the same query can get it stored under its own tags.
 */
class Home implements ResolverInterface
{
    public function __construct(
        private readonly HomeBuilder $builder,
        private readonly CacheableQuery $cacheableQuery,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $audience = isset($args['audience']) && is_string($args['audience']) ? $args['audience'] : null;
        $store = $context->getExtensionAttributes()->getStore();

        try {
            return $this->builder->build($store, $audience);
        } catch (\Throwable $e) {
            $this->cacheableQuery->setCacheValidity(false);
            $this->logger->error(
                sprintf('HubApp: hmAppHome could not be built for store %s: %s', (string) $store->getCode(), $e->getMessage()),
                ['exception' => $e]
            );

            throw $e;
        }
    }
}
