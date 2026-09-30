<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\CatalogGraphQl\Model\Resolver\Product\ProductFieldsSelector;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;

/**
 * HmProductPage.items — the page's ranked ids as ProductInterface values.
 *
 * The list resolvers (hmDeals, hmBestSellers) rank and page ids and leave them
 * under IDS_KEY; loading happens here because only this field's ResolveInfo
 * says which product fields the query wants.
 */
class ProductPageItems implements ResolverInterface
{
    /** Where the list resolvers put the page's ranked product ids. */
    public const IDS_KEY = '_ids';

    private const NODE = 'items';

    public function __construct(
        private readonly ProductFieldsSelector $fieldsSelector,
        private readonly ProductListLoaderInterface $loader
    ) {
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
        $ids = (array) ($value[self::IDS_KEY] ?? []);
        if (!$ids) {
            return [];
        }

        return $this->loader->load(
            $ids,
            $this->fieldsSelector->getProductFieldsFromInfo($info, self::NODE),
            $context
        );
    }
}
