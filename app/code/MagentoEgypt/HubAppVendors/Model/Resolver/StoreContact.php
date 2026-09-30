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
 * HmStore.contact — the ways to reach the seller that the store page publishes.
 *
 * The page publishes one: the seller's telephone, which its "Contact Vendor"
 * button dials (the theme's profile/contact.phtml: Vnecoms has no contact or
 * enquiry route on this install). Nothing to reach, no contact: null.
 */
class StoreContact implements ResolverInterface
{
    public function __construct(private readonly SellerProfile $profile)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $phone = $this->profile->phone((int) ($value['card']['vendor_entity_id'] ?? 0));

        return $phone !== null ? ['phone' => $phone] : null;
    }
}
