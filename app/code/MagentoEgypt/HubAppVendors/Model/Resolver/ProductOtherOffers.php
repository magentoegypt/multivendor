<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use MagentoEgypt\HubApp\Model\Resolver\Paging;

/**
 * ProductInterface.hm_other_offers(pageSize) — the other sellers' offers on the
 * product, cheapest first, as the website's price comparison lists them
 * (OfferFinder). The website shows five and folds the rest behind "View all N
 * sellers"; the app asks for as many as it shows.
 */
class ProductOtherOffers extends AbstractOfferResolver
{
    public const DEFAULT_PAGE_SIZE = 10;
    public const MAX_PAGE_SIZE = 50;

    /**
     * @inheritDoc
     */
    protected function value(array $offers, BatchRequestItemInterface $request): mixed
    {
        [$pageSize] = Paging::args($request->getArgs(), self::DEFAULT_PAGE_SIZE, self::MAX_PAGE_SIZE);

        return array_slice(array_values($offers), 0, $pageSize);
    }
}
