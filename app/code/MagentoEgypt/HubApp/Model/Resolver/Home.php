<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubApp\Model\Home\HomeBuilder;

/**
 * hmAppHome(audience): the app Home of the store view in the Store header.
 *
 * Public and shared by every viewer of the store view and audience: send it as
 * GET without Authorization so the HTTP cache serves it (HomeIdentity tags).
 * Products of the product sections are loaded per request by
 * HmHomeSection.products (one collection for the whole Home).
 *
 * A section that fails is dropped by the builder; if the Home as a whole
 * cannot be built the error reaches the app, which then keeps its built-in
 * CMS-driven Home — an empty Home would be cached and look intentional.
 */
class Home implements ResolverInterface
{
    public function __construct(private readonly HomeBuilder $builder)
    {
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

        return $this->builder->build($context->getExtensionAttributes()->getStore(), $audience);
    }
}
