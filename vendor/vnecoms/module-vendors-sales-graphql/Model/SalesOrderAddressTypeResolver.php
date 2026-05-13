<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * {@inheritdoc}
 */
class SalesOrderAddressTypeResolver implements TypeResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveType(array $data): string
    {
        if (isset($data['address_type'])) {
            if ($data['address_type'] == 'billing') {
                return 'VendorOrderBillingAddress';
            } elseif ($data['address_type'] == 'shipping') {
                return 'VendorOrderShippingAddress';
            }
        }
        return '';
    }
}
