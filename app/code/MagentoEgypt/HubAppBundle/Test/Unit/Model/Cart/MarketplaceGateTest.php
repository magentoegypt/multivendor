<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Test\Unit\Model\Cart;

use MagentoEgypt\HubAppBundle\Model\Cart\MarketplaceGate;
use MagentoEgypt\VendorExtend\Model\StorefrontVisibility;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MagentoEgypt\HubAppBundle\Model\Cart\MarketplaceGate
 */
class MarketplaceGateTest extends TestCase
{
    public function testSelectedProductIdsFollowTheBuyRequest(): void
    {
        $selections = [
            1 => ['option_id' => 10, 'product_id' => 501, 'type_id' => 'simple'],
            2 => ['option_id' => 10, 'product_id' => 502, 'type_id' => 'configurable'],
            3 => ['option_id' => 11, 'product_id' => 503, 'type_id' => 'simple'],
            4 => ['option_id' => 11, 'product_id' => 504, 'type_id' => 'virtual'],
        ];
        $request = ['product' => 900, 'qty' => 1.0, 'bundle_option' => [10 => 2, 11 => [3, 4]]];

        self::assertSame([502, 503, 504], MarketplaceGate::selectedProductIds($request, $selections));
        self::assertSame([], MarketplaceGate::selectedProductIds(['bundle_option' => [10 => 99]], $selections));
    }

    public function testTheBundleMustBeShownInTheStoreView(): void
    {
        $visibility = $this->createMock(StorefrontVisibility::class);
        $visibility->method('sellableIds')->willReturnCallback(
            static fn (array $ids, int $storeId): array => $storeId === 1 ? $ids : []
        );
        $gate = new MarketplaceGate($visibility);

        self::assertTrue($gate->allowsBundle(900, 1));
        self::assertFalse($gate->allowsBundle(900, 2), 'unapproved, inactive seller, disabled or not visible');
        self::assertFalse($gate->allowsBundle(0, 1));
    }

    public function testEverySelectedProductMustBeApprovedAndOfAnActiveSeller(): void
    {
        $visibility = $this->createMock(StorefrontVisibility::class);
        $visibility->expects(self::never())->method('sellableIds');
        $visibility->method('approvedIds')->willReturnCallback(
            static fn (array $ids): array => array_values(array_diff($ids, [503]))
        );
        $gate = new MarketplaceGate($visibility);

        self::assertTrue($gate->allowsSelections([501, 502, 501]));
        self::assertFalse($gate->allowsSelections([502, 503]));
        self::assertTrue($gate->allowsSelections([]));
    }
}
