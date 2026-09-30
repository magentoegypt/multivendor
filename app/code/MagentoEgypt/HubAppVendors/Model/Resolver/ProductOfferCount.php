<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;

/**
 * ProductInterface.hm_offer_count — "Sold by N other sellers": how many other
 * sellers the website's price comparison lists for the product (0 when none).
 */
class ProductOfferCount extends AbstractOfferResolver
{
    /**
     * @inheritDoc
     */
    protected function value(array $offers, BatchRequestItemInterface $request): mixed
    {
        return count($offers);
    }
}
