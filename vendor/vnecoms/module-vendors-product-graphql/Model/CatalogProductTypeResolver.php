<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsProductGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * {@inheritdoc}
 */
class CatalogProductTypeResolver implements TypeResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveType(array $data) : string
    {
        if (isset($data['type_id'])) {
            switch ($data['type_id']) {
                case 'simple':
                    return 'VendorSimpleProduct';
                case 'virtual':
                    return 'VendorVirtualProduct';
                case 'configurable':
                    return 'VendorConfigurableProduct';
                case 'download':
                    return 'VendorDownloadableProduct';
                case 'grouped':
                    return 'VendorGroupedProduct';
                case 'bundle':
                    return 'VendorBundleProduct';
            }
        }
        return '';
    }
}
