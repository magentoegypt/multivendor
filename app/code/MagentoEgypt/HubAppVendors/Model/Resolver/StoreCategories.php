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
 * Query.hmStoreCategories — the Stores chips: top-level menu categories with
 * how many sellers each holds, as hmStores' category_id filter counts them.
 * Public; GET (StoreCategoriesIdentity: hm_vendor and the categories' tags).
 */
class StoreCategories implements ResolverInterface
{
    public function __construct(private readonly SellerCategories $categories)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        return $this->categories->chips((int) $context->getExtensionAttributes()->getStore()->getId());
    }
}
