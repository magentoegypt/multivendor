<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Offer;

use Magento\Catalog\Model\Product\Visibility;
use MagentoEgypt\HubAppVendors\Model\Offer\OfferGate;
use PHPUnit\Framework\TestCase;

/**
 * The website's per-offer rule (Vnecoms LoadProduct / Process::checkProductSalesEnable)
 * after the storefront gate.
 */
final class OfferGateTest extends TestCase
{
    public function testHubMarketsOwnProductsAreNeverAnotherSeller(): void
    {
        self::assertFalse(OfferGate::offerable(0, 'simple', Visibility::VISIBILITY_BOTH));
        self::assertFalse(OfferGate::offerable(-1, 'configurable', Visibility::VISIBILITY_BOTH));
    }

    public function testASimpleOfferMustAlsoBeVisibleInSearch(): void
    {
        self::assertTrue(OfferGate::offerable(14, 'simple', Visibility::VISIBILITY_BOTH));
        self::assertTrue(OfferGate::offerable(14, 'virtual', Visibility::VISIBILITY_IN_SEARCH));
        self::assertFalse(OfferGate::offerable(14, 'simple', Visibility::VISIBILITY_IN_CATALOG));
        self::assertFalse(OfferGate::offerable(14, 'simple', Visibility::VISIBILITY_NOT_VISIBLE));
    }

    public function testAConfigurableOfferIsJudgedByItsChildrenNotItsSearchVisibility(): void
    {
        self::assertTrue(OfferGate::offerable(4, OfferGate::TYPE_CONFIGURABLE, Visibility::VISIBILITY_IN_CATALOG));
    }

    public function testNoVisibilityValueSkipsTheSearchCheckAsTheWebsiteDoes(): void
    {
        self::assertTrue(OfferGate::offerable(4, 'simple', null));
    }
}
