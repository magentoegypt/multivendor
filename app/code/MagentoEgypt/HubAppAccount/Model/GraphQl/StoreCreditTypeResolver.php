<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\GraphQl;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * GraphQL concrete type of Vnecoms' store credit product (`store_credit`, the credit top-up the
 * website sells).
 *
 * Every product in a GraphQL answer must resolve to a concrete type, and nothing registered one for
 * `store_credit`: a product list or a cart holding one failed as a whole with "Concrete type for
 * ProductInterface not implemented". Its type model (Vnecoms\Credit\Model\Product\Type\Credit)
 * extends Magento's virtual type, so it answers as VirtualProduct. Core's product type resolvers
 * have no configurable map, hence this class (the same shape as BundleExtend's
 * NewBundleTypeResolver); the rest of the mapping is in etc/di.xml and etc/graphql/di.xml.
 */
class StoreCreditTypeResolver implements TypeResolverInterface
{
    /** Vnecoms\Credit\Model\Product\Type\Credit::TYPE_CODE */
    public const TYPE_ID = 'store_credit';

    public function resolveType(array $data): string
    {
        return ($data['type_id'] ?? null) === self::TYPE_ID ? 'VirtualProduct' : '';
    }
}
