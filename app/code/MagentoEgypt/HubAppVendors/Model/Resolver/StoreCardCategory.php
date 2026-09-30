<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppVendors\Model\Store\SellerCategories;

/**
 * HmStoreCard.primary_category — the top-level menu category holding most of
 * the seller's listable products (SellerCategories), on every card: the
 * Stores list, a store page and the Home's store sections alike.
 */
class StoreCardCategory implements ResolverInterface
{
    public function __construct(private readonly SellerCategories $categories)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $vendorId = (int) ($value['vendor_entity_id'] ?? 0);
        if ($vendorId < 1) {
            return null;
        }

        return $this->categories->primary($vendorId, (int) $context->getExtensionAttributes()->getStore()->getId());
    }
}
