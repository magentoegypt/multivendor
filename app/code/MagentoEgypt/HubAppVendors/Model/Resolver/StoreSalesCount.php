<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppVendors\Model\Store\SellerProfile;

/**
 * HmStore.sales_count — the store page's "N Sales" (SellerProfile); null while the admin hides it.
 */
class StoreSalesCount implements ResolverInterface
{
    public function __construct(private readonly SellerProfile $profile)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        return $this->profile->salesCount((int) ($value['card']['vendor_entity_id'] ?? 0));
    }
}
