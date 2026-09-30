<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Plugin\BundleGraphQl;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Lets BundleGraphQl's product field resolvers answer for `new_bundle`.
 *
 * BundleExtend makes `new_bundle` answer as BundleProduct, but BundleItems,
 * DynamicPrice, DynamicSku, DynamicWeight, PriceView and ShipBundleItems each
 * check `$value['type_id'] === 'bundle'` and return null otherwise, so a
 * new_bundle PDP came back with no `items` at all. `new_bundle` runs on the
 * core bundle type model (BundleExtend etc/product_types.xml), so the data those
 * resolvers read (options, selections, price/sku/weight/shipment type) is the
 * same; only the type code differs.
 *
 * Around each of those resolvers: a new_bundle value is passed on as `bundle`.
 * $value is an array (a copy), so no other resolver sees the change.
 */
class TreatNewBundleAsBundle
{
    public const NEW_BUNDLE = 'new_bundle';
    public const BUNDLE = 'bundle';

    /**
     * @param ResolverInterface $subject
     * @param callable $proceed
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundResolve(
        ResolverInterface $subject,
        callable $proceed,
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if (is_array($value) && ($value['type_id'] ?? null) === self::NEW_BUNDLE) {
            $value['type_id'] = self::BUNDLE;
        }

        return $proceed($field, $context, $info, $value, $args);
    }
}
