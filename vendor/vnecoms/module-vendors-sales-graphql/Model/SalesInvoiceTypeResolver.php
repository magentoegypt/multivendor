<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * {@inheritdoc}
 */
class SalesInvoiceTypeResolver implements TypeResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveType(array $data): string
    {
        return 'VendorOrderInvoice';
    }
}
