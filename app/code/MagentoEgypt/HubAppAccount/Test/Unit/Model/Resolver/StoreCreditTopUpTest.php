<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\Resolver;

use MagentoEgypt\HubAppAccount\Model\Credit\TopUpCatalog;
use MagentoEgypt\HubAppAccount\Model\Credit\TopUpOptions;
use MagentoEgypt\HubAppAccount\Model\Resolver\StoreCreditTopUp;
use PHPUnit\Framework\TestCase;

/**
 * HmStoreCreditAccount.top_up as the app reads it.
 */
final class StoreCreditTopUpTest extends TestCase
{
    public function testNullWhenTheStoreSellsNoCredit(): void
    {
        $catalog = $this->createMock(TopUpCatalog::class);
        $catalog->method('forStore')->with(2)->willReturn(null);
        $catalog->expects(self::never())->method('currency');

        self::assertNull((new StoreCreditTopUp($catalog))->forStore(2));
    }

    public function testPresetsAndTheCustomRangeInTheBaseCurrency(): void
    {
        $catalog = $this->createMock(TopUpCatalog::class);
        $catalog->method('forStore')->with(1)->willReturn(TopUpOptions::fromProducts([
            ['id' => 5, 'sku' => 'credit-any', 'credit_type' => '3', 'credit_value_custom' => '{"from":"10","to":"1000"}', 'credit_rate' => '1'],
            ['id' => 6, 'sku' => 'credit-100', 'credit_type' => '1', 'credit_value_fixed' => '100', 'credit_price' => '95'],
        ]));
        $catalog->method('currency')->with(1)->willReturn('AED');

        self::assertSame(
            [
                'sku' => 'credit-any',
                'min' => ['value' => 10.0, 'currency' => 'AED'],
                'max' => ['value' => 1000.0, 'currency' => 'AED'],
                'credit_rate' => 1.0,
                'presets' => [
                    [
                        'sku' => 'credit-100',
                        'credit' => ['value' => 100.0, 'currency' => 'AED'],
                        'price' => ['value' => 95.0, 'currency' => 'AED'],
                    ],
                ],
            ],
            (new StoreCreditTopUp($catalog))->forStore(1)
        );
    }

    public function testPresetsOnlyHaveNoRange(): void
    {
        $catalog = $this->createMock(TopUpCatalog::class);
        $catalog->method('forStore')->willReturn(TopUpOptions::fromProducts([
            ['id' => 6, 'sku' => 'credit-pack', 'credit_type' => '2', 'credit_value_dropdown' => [
                ['credit_value' => '50', 'credit_price' => '50'],
                ['credit_value' => '100', 'credit_price' => '100'],
            ]],
        ]));
        $catalog->method('currency')->willReturn('AED');

        $topUp = (new StoreCreditTopUp($catalog))->forStore(1);

        self::assertSame('credit-pack', $topUp['sku']);
        self::assertNull($topUp['min']);
        self::assertNull($topUp['max']);
        self::assertNull($topUp['credit_rate']);
        self::assertSame([50.0, 100.0], array_map(static fn (array $p): float => $p['credit']['value'], $topUp['presets']));
    }
}
