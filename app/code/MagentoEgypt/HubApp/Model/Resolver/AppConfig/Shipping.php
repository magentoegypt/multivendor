<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver\AppConfig;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use MagentoEgypt\HubApp\Model\Config\FreeShippingThreshold;

/**
 * HmAppConfig.shipping: the store view's shipping promises.
 *
 * free_over is the storefront mini-cart's free-shipping threshold
 * (FreeShippingThreshold) for the caller's customer group: guests, as the app
 * sends hmAppConfig without a token, exactly as the website's full-page-cached
 * mini-cart does. In the request's currency, rounded to the cent.
 */
class Shipping implements ResolverInterface
{
    /** Magento\Customer\Model\Group::NOT_LOGGED_IN_ID */
    private const GUEST_GROUP = 0;

    public function __construct(
        private readonly FreeShippingThreshold $threshold,
        private readonly PriceCurrencyInterface $priceCurrency
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
        $extension = $context->getExtensionAttributes();
        $store = $extension->getStore();
        $group = $extension->getCustomerGroupId();

        $base = $this->threshold->forStore((int) $store->getId(), $group !== null ? (int) $group : self::GUEST_GROUP);

        return [
            'free_over' => $base === null ? null : [
                'value' => round((float) $this->priceCurrency->convert($base, $store), 2),
                'currency' => (string) $store->getCurrentCurrencyCode(),
            ],
        ];
    }
}
