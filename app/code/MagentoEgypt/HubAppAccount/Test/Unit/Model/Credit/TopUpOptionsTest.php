<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\Credit;

use MagentoEgypt\HubAppAccount\Model\Credit\TopUpOptions;
use PHPUnit\Framework\TestCase;

/**
 * The top-up the app offers from the website's store credit products, and the buy request its product
 * page posts for an amount (Vnecoms\Credit\Model\Product\Type\Credit::_prepareProduct reads it).
 */
final class TopUpOptionsTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function product(int $id, string $sku, int $type, array $data): array
    {
        return ['id' => $id, 'sku' => $sku, 'credit_type' => (string) $type] + $data;
    }

    private static function fixed(int $id, string $sku, string $credit, ?string $price): array
    {
        return self::product($id, $sku, TopUpOptions::TYPE_FIXED, [
            'credit_value_fixed' => $credit,
            'credit_price' => $price,
        ]);
    }

    /**
     * @param mixed $options as stored: JSON text, or the array the attribute backend decodes it to
     */
    private static function dropdown(int $id, string $sku, mixed $options): array
    {
        return self::product($id, $sku, TopUpOptions::TYPE_DROPDOWN, ['credit_value_dropdown' => $options]);
    }

    /**
     * @param mixed $range as stored: JSON text, or the decoded array
     */
    private static function custom(int $id, string $sku, mixed $range, mixed $rate = '1.0000'): array
    {
        return self::product($id, $sku, TopUpOptions::TYPE_CUSTOM, [
            'credit_value_custom' => $range,
            'credit_rate' => $rate,
        ]);
    }

    public function testNoProductSellsNothing(): void
    {
        self::assertNull(TopUpOptions::fromProducts([]));
    }

    public function testFixedValueProductsArePresetsSmallestFirst(): void
    {
        $options = TopUpOptions::fromProducts([
            self::fixed(12, 'credit-250', '250', '250.0000'),
            self::fixed(10, 'credit-50', '50', '50.0000'),
            self::fixed(11, 'credit-100', '100', '95.0000'),
        ]);

        self::assertNotNull($options);
        self::assertSame(
            [[50.0, 50.0, 'credit-50'], [100.0, 95.0, 'credit-100'], [250.0, 250.0, 'credit-250']],
            array_map(static fn (array $p): array => [$p['credit'], $p['price'], $p['sku']], $options->presets())
        );
        self::assertNull($options->min());
        self::assertNull($options->max());
        self::assertNull($options->rate());
        //  Without a custom amount, the smallest preset's product.
        self::assertSame('credit-50', $options->sku());
    }

    public function testAFixedValueIsBoughtWithTheProductAloneAsTheProductPagePostsIt(): void
    {
        $options = TopUpOptions::fromProducts([self::fixed(10, 'credit-50', '50', '50')]);

        self::assertSame(
            ['product_id' => 10, 'sku' => 'credit-50', 'request' => ['product' => 10, 'qty' => 1]],
            $options->buyRequest(50.0)
        );
        self::assertNull($options->buyRequest(60.0));
    }

    public function testDropdownValuesArePresetsBoughtWithTheirValue(): void
    {
        $json = '[{"credit_value":"100","credit_price":"90"},{"credit_value":"50","credit_price":"50"},'
            . '{"credit_value":"250","credit_price":"225"}]';
        foreach ([$json, json_decode($json, true)] as $stored) {
            $options = TopUpOptions::fromProducts([self::dropdown(20, 'credit-pack', $stored)]);

            self::assertNotNull($options);
            self::assertSame([50.0, 100.0, 250.0], array_column($options->presets(), 'credit'));
            self::assertSame([50.0, 90.0, 225.0], array_column($options->presets(), 'price'));
            self::assertSame(
                [
                    'product_id' => 20,
                    'sku' => 'credit-pack',
                    'request' => ['product' => 20, 'qty' => 1, 'store_credit' => ['credit_value' => '100']],
                ],
                $options->buyRequest(100)
            );
        }
    }

    public function testCustomValueAllowsWholeAmountsWithinTheRangeOnly(): void
    {
        $options = TopUpOptions::fromProducts([
            self::custom(30, 'credit-any', '{"from":"10","to":"1000"}', '1.2500'),
        ]);

        self::assertNotNull($options);
        self::assertSame([], $options->presets());
        self::assertSame(10, $options->min());
        self::assertSame(1000, $options->max());
        self::assertSame(1.25, $options->rate());
        self::assertSame('credit-any', $options->sku());
        self::assertSame(
            [
                'product_id' => 30,
                'sku' => 'credit-any',
                'request' => ['product' => 30, 'qty' => 1, 'store_credit' => ['credit_value' => 125]],
            ],
            $options->buyRequest(125.0)
        );
        //  The page's slider and field only give whole numbers from..to; _prepareProduct checks neither.
        self::assertNotNull($options->buyRequest(10));
        self::assertNotNull($options->buyRequest(1000));
        self::assertNull($options->buyRequest(9));
        self::assertNull($options->buyRequest(1001));
        self::assertNull($options->buyRequest(12.5));
        self::assertNull($options->buyRequest(0));
        self::assertNull($options->buyRequest(-20));
        self::assertNull($options->buyRequest(NAN));
        self::assertNull($options->buyRequest(INF));
        //  credit_value / credit_rate, rounded to cents as _prepareProduct rounds it.
        self::assertSame(80.0, $options->customPrice(100));
        self::assertSame(26.67, TopUpOptions::fromProducts([
            self::custom(31, 'credit-third', ['from' => 1, 'to' => 100], '1.5'),
        ])->customPrice(40));
    }

    public function testARangeFromZeroStartsAtOne(): void
    {
        //  0 credit is refused by _prepareProduct ("You need to choose options for your item.").
        $options = TopUpOptions::fromProducts([self::custom(30, 'credit-any', ['from' => '0', 'to' => '500'])]);

        self::assertSame(1, $options->min());
        self::assertNull($options->buyRequest(0.4));
    }

    public function testPresetsAndACustomAmountTogether(): void
    {
        $options = TopUpOptions::fromProducts([
            self::custom(40, 'credit-any', ['from' => 20, 'to' => 2000]),
            self::fixed(41, 'credit-100', '100', '100'),
        ]);

        self::assertSame([100.0], array_column($options->presets(), 'credit'));
        self::assertSame('credit-any', $options->sku());
        //  A preset's amount goes to its own product, any other to the custom-amount product.
        self::assertSame(41, $options->buyRequest(100)['product_id']);
        self::assertSame(40, $options->buyRequest(150)['product_id']);
    }

    public function testOnePresetPerAmountTheCheaperKept(): void
    {
        $options = TopUpOptions::fromProducts([
            self::fixed(51, 'credit-100-a', '100', '100'),
            self::dropdown(52, 'credit-pack', [['credit_value' => '100', 'credit_price' => '95']]),
            self::fixed(53, 'credit-100-b', '100.00', '100'),
        ]);

        self::assertCount(1, $options->presets());
        self::assertSame('credit-pack', $options->presets()[0]['sku']);
        self::assertSame(95.0, $options->presets()[0]['price']);
    }

    public function testTheFirstCustomValueProductByIdIsTheOneUsed(): void
    {
        $options = TopUpOptions::fromProducts([
            self::custom(61, 'credit-later', ['from' => 1, 'to' => 50]),
            self::custom(60, 'credit-first', ['from' => 5, 'to' => 500]),
        ]);

        self::assertSame('credit-first', $options->sku());
        self::assertSame(5, $options->min());
        self::assertSame(500, $options->max());
    }

    public function testProductsThatWouldNotSellProperlyAreLeftOut(): void
    {
        self::assertNull(TopUpOptions::fromProducts([
            //  No credit_price: the product page would give the credit away.
            self::fixed(70, 'free-credit', '100', null),
            self::fixed(71, 'zero-credit', '0', '10'),
            self::dropdown(72, 'no-options', '[]'),
            self::dropdown(73, 'broken-json', '[{"credit_value":'),
            self::dropdown(74, 'free-option', [['credit_value' => '10', 'credit_price' => '0']]),
            //  credit_rate 0: _prepareProduct would divide by zero.
            self::custom(75, 'no-rate', ['from' => 1, 'to' => 10], '0'),
            self::custom(76, 'no-range', null),
            self::custom(77, 'upside-down', ['from' => 100, 'to' => 10]),
            self::custom(78, 'not-numbers', ['from' => 'a', 'to' => 'b']),
            self::product(79, 'unknown-type', 9, ['credit_value_fixed' => '10', 'credit_price' => '10']),
            self::fixed(0, 'no-id', '10', '10'),
            self::fixed(80, '', '10', '10'),
        ]));
    }
}
