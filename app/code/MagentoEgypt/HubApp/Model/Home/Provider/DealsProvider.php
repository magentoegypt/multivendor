<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home\Provider;

use MagentoEgypt\HomeSections\Model\Ranking\DealRanker;
use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;
use MagentoEgypt\HubApp\Model\Product\RankedLists;
use MagentoEgypt\HubApp\Model\Resolver\Home\SectionProducts;

/**
 * TODAYS_DEALS: live special prices, deepest discount first, and the countdown
 * to the end of the soonest-ending offer SHOWN (23:59:59 store time of its
 * special_to_date). No live offer: no section — a sale is never invented.
 *
 * Each offer's end date travels under SectionProducts::ENDS_KEY, so the
 * builder can recompute the countdown when a cached Home loses a product
 * (sold out since) at request time.
 */
class DealsProvider implements SectionProviderInterface
{
    public function __construct(
        private readonly RankedLists $lists,
        private readonly DealRanker $dealRanker
    ) {
    }

    public function provide(SectionContext $context): ?SectionResult
    {
        $rows = array_slice($this->lists->deals($context->getStoreId()), 0, $context->getLimit());
        if (!$rows) {
            return null;
        }

        return SectionResult::create()
            ->withProductIds(array_column($rows, 'id'))
            ->withField('countdown_ends_at', $this->dealRanker->countdown($rows, $context->getStoreId()))
            ->withField(SectionProducts::ENDS_KEY, array_column($rows, 'to_date', 'id'))
            ->withTags([Tags::APP_CATALOG]);
    }
}
