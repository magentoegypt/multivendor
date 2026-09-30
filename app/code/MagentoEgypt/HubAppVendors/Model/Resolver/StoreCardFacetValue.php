<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppVendors\Model\Store\SellerFacetValues;

/**
 * HmStoreCard.facet_value — the seller's value in the storefront's Algolia
 * seller facet for this store view (SellerFacetValues), so search can match a
 * facet count to its store exactly.
 */
class StoreCardFacetValue implements ResolverInterface
{
    public function __construct(private readonly SellerFacetValues $facetValues)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        return $this->facetValues->forVendor(
            (int) ($value['vendor_entity_id'] ?? 0),
            (int) $context->getExtensionAttributes()->getStore()->getId()
        );
    }
}
