<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver\AppConfig;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubApp\Model\Config\AlgoliaLayout;

/**
 * The search-layout fields of HmAlgoliaConfig (facets, sorts, suggestion
 * index and count, currency, price group, facet size, autocomplete counts,
 * category separator): read once per store view and request (AlgoliaLayout)
 * and only when a query asks for one of them.
 */
class AlgoliaLayoutField implements ResolverInterface
{
    private const PRODUCTS_SUFFIX = '_products';

    public function __construct(private readonly AlgoliaLayout $layout)
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
        $store = $context->getExtensionAttributes()->getStore();
        //  AppConfigReader::algolia(): product_index = <index prefix><store code>_products.
        $productIndex = (string) ($value['product_index'] ?? '');
        $indexName = str_ends_with($productIndex, self::PRODUCTS_SUFFIX)
            ? substr($productIndex, 0, -strlen(self::PRODUCTS_SUFFIX))
            : (string) ($value['index_prefix'] ?? '') . $store->getCode();

        return $this->layout->forStore($store, $indexName)[$field->getName()] ?? null;
    }
}
