<?php
declare(strict_types=1);

namespace MagentoEgypt\BundleExtend\Model\GraphQl;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * GraphQL concrete type for `new_bundle` products.
 *
 * Every product in a GraphQL answer must resolve to a concrete type. Each core product type
 * registers a resolver with ProductInterfaceTypeResolverComposite (bundle → BundleProduct,
 * and so on), and nothing registered one for this module's `new_bundle`. Any query whose
 * results included one of those products failed as a whole with "Concrete type for
 * ProductInterface not implemented" (2026-09-26, 4 products).
 *
 * `new_bundle` runs on Magento's own bundle type model (etc/product_types.xml), so it answers
 * as BundleProduct. BundleGraphQl's field resolvers (items, dynamic_price, dynamic_sku,
 * price_view, ship_bundle_items) check type_id === 'bundle' and return null for it. Those
 * fields are all nullable, so the product resolves with its common fields and no bundle
 * items, instead of failing the whole query.
 */
class NewBundleTypeResolver implements TypeResolverInterface
{
    private const TYPE_ID = 'new_bundle';

    public function resolveType(array $data): string
    {
        return ($data['type_id'] ?? null) === self::TYPE_ID ? 'BundleProduct' : '';
    }
}
