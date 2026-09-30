<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubApp\Model\Config\Capabilities as CapabilityList;

/**
 * HmAppConfig.capabilities: the HubApp satellites deployed and enabled here.
 *
 * The same for every store view and caller, so it rides on hmAppConfig's
 * cache entry (hm_app_config); enabling or disabling a module flushes the
 * caches anyway.
 */
class Capabilities implements ResolverInterface
{
    public function __construct(private readonly CapabilityList $capabilities)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        return $this->capabilities->codes();
    }
}
