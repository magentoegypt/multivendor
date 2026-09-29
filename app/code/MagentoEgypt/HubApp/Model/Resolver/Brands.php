<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubApp\Model\Brand\BrandReader;

/**
 * hmBrands: enabled MGS brands of the store view, admin order, names through
 * the theme translations. Products of a brand: products(filter: mgs_brand eq
 * option_id).
 */
class Brands implements ResolverInterface
{
    public function __construct(private readonly BrandReader $brandReader)
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
        [$pageSize, $currentPage] = Paging::args($args, 100, BrandReader::MAX);
        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();

        $page = $this->brandReader->page($storeId, !empty($args['featured_only']), $pageSize, $currentPage);

        return [
            'items' => $page['items'],
            'total_count' => $page['total_count'],
            'page_info' => Paging::info($page['total_count'], $pageSize, $currentPage),
        ];
    }
}
