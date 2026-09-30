<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubApp\Model\Config\AppConfigReader;

/**
 * hmAppConfig(platform): launch settings of the store view in the Store header.
 *
 * Public and cacheable: nothing in it depends on the caller, so it is sent as
 * GET and served from the HTTP cache (tag hm_app_config, purged when the
 * settings are saved). With a platform, version policies and flags are that
 * platform's only; without one, both policies and the all-platform flags.
 */
class AppConfig implements ResolverInterface
{
    public function __construct(private readonly AppConfigReader $reader)
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
        $store = $context->getExtensionAttributes()->getStore();
        $platform = isset($args['platform']) && is_string($args['platform']) ? $args['platform'] : null;

        return $this->reader->read($store, $platform);
    }
}
