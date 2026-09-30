<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home\Provider;

use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;
use MagentoEgypt\HubApp\Model\Brand\BrandReader;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;

/**
 * TOP_BRANDS: MGS brands of the store view in admin order, as the website's
 * Top Brands row; the section's "featured only" option keeps the flagged ones.
 */
class BrandsProvider implements SectionProviderInterface
{
    public function __construct(private readonly BrandReader $brandReader)
    {
    }

    public function provide(SectionContext $context): ?SectionResult
    {
        $page = $this->brandReader->page(
            $context->getStoreId(),
            (bool) $context->getOption('featured_only', false),
            $context->getLimit(),
            1
        );
        if (!$page['items']) {
            return null;
        }

        return SectionResult::create()->withField('brands', $page['items'])->withTags([Tags::BRAND]);
    }
}
