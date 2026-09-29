<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Test\Unit\Model\Cart;

use Magento\Framework\Exception\LocalizedException;
use MagentoEgypt\HubAppBundle\Model\Cart\BundleBuyRequestBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MagentoEgypt\HubAppBundle\Model\Cart\BundleBuyRequestBuilder
 */
class BundleBuyRequestBuilderTest extends TestCase
{
    private const BUNDLE_ID = 900;

    private BundleBuyRequestBuilder $builder;

    /**
     * The bundle: option 10 (radio: simple 501 or configurable 502), option 11 (checkbox: simples
     * 503 and 504), option 12 (a second configurable, 505).
     *
     * @var array<int, array{option_id: int, product_id: int, type_id: string}>
     */
    private array $selections = [
        1 => ['option_id' => 10, 'product_id' => 501, 'type_id' => 'simple'],
        2 => ['option_id' => 10, 'product_id' => 502, 'type_id' => 'configurable'],
        3 => ['option_id' => 11, 'product_id' => 503, 'type_id' => 'simple'],
        4 => ['option_id' => 11, 'product_id' => 504, 'type_id' => 'virtual'],
        5 => ['option_id' => 12, 'product_id' => 505, 'type_id' => 'configurable'],
    ];

    protected function setUp(): void
    {
        $this->builder = new BundleBuyRequestBuilder();
    }

    private static function selectionUid(int $optionId, int $selectionId, int $qty = 1): string
    {
        return base64_encode('bundle/' . $optionId . '/' . $selectionId . '/' . $qty);
    }

    private static function valueUid(int $attributeId, int $value): string
    {
        return base64_encode('configurable/' . $attributeId . '/' . $value);
    }

    public function testSimpleSelectionsGiveScalarOptionsAndNoQuantities(): void
    {
        $request = $this->builder->build(self::BUNDLE_ID, 1.0, [
            ['selection_uid' => self::selectionUid(10, 1)],
        ], $this->selections);

        $this->assertSame([
            'product' => self::BUNDLE_ID,
            'qty' => 1.0,
            'bundle_option' => [10 => 1],
        ], $request);
    }

    public function testSeveralSelectionsOfOneOptionBecomeAList(): void
    {
        $request = $this->builder->build(self::BUNDLE_ID, 2.0, [
            ['selection_uid' => self::selectionUid(10, 1)],
            ['selection_uid' => self::selectionUid(11, 3), 'quantity' => 2],
            ['selection_uid' => self::selectionUid(11, 4)],
        ], $this->selections);

        $this->assertSame(2.0, $request['qty']);
        $this->assertSame([10 => 1, 11 => [3, 4]], $request['bundle_option']);
        //  Only the selection with a quantity is sent, keyed by selection id.
        $this->assertSame([11 => [3 => 2.0]], $request['bundle_option_qty']);
        $this->assertArrayNotHasKey('super_attribute', $request);
    }

    public function testQuantityOfASingleSelectionIsScalar(): void
    {
        $request = $this->builder->build(self::BUNDLE_ID, 1.0, [
            ['selection_uid' => self::selectionUid(10, 1), 'quantity' => 3],
        ], $this->selections);

        $this->assertSame([10 => 3.0], $request['bundle_option_qty']);
    }

    public function testConfigurableChoicesAreKeyedByChildProductId(): void
    {
        $request = $this->builder->build(self::BUNDLE_ID, 1.0, [
            [
                'selection_uid' => self::selectionUid(10, 2),
                'configurable_option_uids' => [self::valueUid(142, 7), self::valueUid(93, 51)],
            ],
            [
                'selection_uid' => self::selectionUid(12, 5),
                'configurable_option_uids' => [self::valueUid(93, 49)],
            ],
        ], $this->selections);

        $this->assertSame([10 => 2, 12 => 5], $request['bundle_option']);
        //  super_attribute[<child id>][<attribute id>] — the website's form, attributes sorted.
        $this->assertSame([502 => [93 => 51, 142 => 7], 505 => [93 => 49]], $request['super_attribute']);
    }

    /**
     * @return array<string, array{array<int, array<string, mixed>>, float, string}>
     */
    public static function refusedCases(): array
    {
        $uid = static fn (int $option, int $selection): string => base64_encode("bundle/{$option}/{$selection}/1");
        $value = static fn (int $attribute, int $v): string => base64_encode("configurable/{$attribute}/{$v}");

        return [
            'no selections' => [[], 1.0, 'Please specify product option(s).'],
            'zero bundles' => [[['selection_uid' => $uid(10, 1)]], 0.0, 'The product quantity should be greater than 0'],
            'not base64' => [[['selection_uid' => '###']], 1.0, 'is not valid'],
            'not a bundle uid' => [[['selection_uid' => base64_encode('configurable/93/51')]], 1.0, 'is not valid'],
            'another bundle\'s selection' => [[['selection_uid' => $uid(10, 77)]], 1.0, 'not available'],
            'selection under the wrong option' => [[['selection_uid' => $uid(11, 1)]], 1.0, 'not available'],
            'same selection twice' => [
                [['selection_uid' => $uid(11, 3)], ['selection_uid' => $uid(11, 3)]],
                1.0,
                'more than once',
            ],
            'zero selection quantity' => [
                [['selection_uid' => $uid(10, 1), 'quantity' => 0]],
                1.0,
                'The product quantity should be greater than 0',
            ],
            'configurable without choices' => [[['selection_uid' => $uid(10, 2)]], 1.0, 'Choose the options'],
            'choices on a simple product' => [
                [['selection_uid' => $uid(10, 1), 'configurable_option_uids' => [$value(93, 51)]]],
                1.0,
                'has no options to choose',
            ],
            'malformed value uid' => [
                [['selection_uid' => $uid(10, 2), 'configurable_option_uids' => [base64_encode('configurable/93')]]],
                1.0,
                'is not valid',
            ],
            'two values for one attribute' => [
                [[
                    'selection_uid' => $uid(10, 2),
                    'configurable_option_uids' => [$value(93, 51), $value(93, 52)],
                ]],
                1.0,
                'one value per configurable option',
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $selections
     */
    #[DataProvider('refusedCases')]
    public function testRefused(array $selections, float $quantity, string $message): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage($message);

        $this->builder->build(self::BUNDLE_ID, $quantity, $selections, $this->selections);
    }
}
