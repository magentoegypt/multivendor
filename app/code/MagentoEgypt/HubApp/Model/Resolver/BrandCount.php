<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubApp\Model\Brand\BrandCounts;

/**
 * HmBrand.product_count and HmBrand.seller_count, wherever a brand is served
 * (hmBrands, the Home's TOP_BRANDS). Resolved per field so a query that does
 * not ask for them costs nothing; every brand of the store view is counted
 * at once on first use (BrandCounts).
 */
class BrandCount implements ResolverInterface
{
    public function __construct(private readonly BrandCounts $brandCounts)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
        $counts = $this->brandCounts->forOption((int) ($value['option_id'] ?? 0), $storeId);

        return $field->getName() === 'seller_count' ? $counts['sellers'] : $counts['products'];
    }
}
