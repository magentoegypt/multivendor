<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Section;

use Magento\Framework\Exception\LocalizedException;
use MagentoEgypt\HubApp\Model\Section\FormDataMapper;
use PHPUnit\Framework\TestCase;

/**
 * The section form's save whitelist and its options JSON.
 */
final class FormDataMapperTest extends TestCase
{
    private FormDataMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new FormDataMapper();
    }

    public function testToRowWhitelistsAndNormalises(): void
    {
        $row = $this->mapper->toRow([
            'type' => 'category_chips',
            'title_en' => ' Shop ',
            'title_ar' => '',
            'item_limit' => '99',
            'audience' => 'somebody',
            'store_id' => '1',
            'position' => '15',
            'is_active' => 'true',
            'vendor_codes' => ['a', 'b', 'a'],
            'sort_by' => 'newest',
            'form_key' => 'x',
            'section_id' => '4',
        ]);

        self::assertSame('CATEGORY_CHIPS', $row['type']);
        self::assertSame('Shop', $row['title_en']);
        self::assertNull($row['title_ar']);
        self::assertSame(50, $row['item_limit']);
        self::assertSame('all', $row['audience']);
        self::assertSame(1, $row['store_id']);
        self::assertSame(15, $row['position']);
        self::assertSame(1, $row['is_active']);
        self::assertSame('a,b', $row['vendor_codes']);
        self::assertSame('NEWEST', $row['sort_by']);
        self::assertArrayNotHasKey('form_key', $row);
        self::assertArrayNotHasKey('section_id', $row);
    }

    public function testDatesArePostedAsIsoUtcAndStoredAsUtc(): void
    {
        $row = $this->mapper->toRow([
            'type' => 'TODAYS_DEALS',
            'starts_at' => '2026-10-01T21:00:00.000Z',
            'ends_at' => '2026-10-02 00:00:00',
        ]);

        self::assertSame('2026-10-01 21:00:00', $row['starts_at']);
        self::assertSame('2026-10-02 00:00:00', $row['ends_at']);
    }

    public function testChipOptionsBecomeEscapedJsonAndKeepOtherKeys(): void
    {
        $row = $this->mapper->toRow([
            'type' => 'CATEGORY_CHIPS',
            'chip_order' => "super-market\nclothes",
            'chip_icons' => "super-market=\u{1F6D2}\nclothes: \u{1F457}",
            'chip_tints' => "super-market=9\nclothes=x",
            'tile_limit' => '',
        ], ['kept' => 1, 'tile_limit' => 3]);

        self::assertMatchesRegularExpression('/^[\x20-\x7e]*$/', (string) $row['options'], 'utf8 (3-byte) safe');
        $options = json_decode((string) $row['options'], true);
        self::assertSame(['super-market', 'clothes'], $options['order']);
        self::assertSame(['super-market' => "\u{1F6D2}", 'clothes' => "\u{1F457}"], $options['icons']);
        self::assertSame(['super-market' => 1], $options['tints'], 'slot modulo 8, non-numbers dropped');
        self::assertSame(1, $options['kept']);
        self::assertArrayNotHasKey('tile_limit', $options);
    }

    public function testUnknownTypeIsRefused(): void
    {
        $this->expectException(LocalizedException::class);
        $this->mapper->toRow(['type' => 'NOPE']);
    }

    /**
     * The active-order card is placement only: it saves with no content fields and
     * no title (the title is optional; empty means no header).
     */
    public function testActiveOrderNeedsNoContentAndNoTitle(): void
    {
        $row = $this->mapper->toRow(['type' => 'active_order', 'audience' => 'customer', 'position' => '5']);

        self::assertSame('ACTIVE_ORDER', $row['type']);
        self::assertNull($row['title_en']);
        self::assertNull($row['title_ar']);
        self::assertNull($row['category_id']);
        self::assertNull($row['cms_identifier']);
        self::assertNull($row['product_skus']);
        self::assertSame('customer', $row['audience']);
        self::assertSame(5, $row['position']);
    }

    public function testRailNeedsACategory(): void
    {
        $this->expectException(LocalizedException::class);
        $this->mapper->toRow(['type' => 'CATEGORY_RAIL']);
    }

    public function testEndMustFollowStart(): void
    {
        $this->expectException(LocalizedException::class);
        $this->mapper->toRow(['type' => 'TODAYS_DEALS', 'starts_at' => '2026-10-02 00:00:00', 'ends_at' => '2026-10-01 00:00:00']);
    }

    public function testToFormUnfoldsTheOptions(): void
    {
        $form = $this->mapper->toForm([
            'type' => 'CATEGORY_CHIPS',
            'vendor_codes' => 'a,b',
            'category_id' => 5,
            'options' => json_encode([
                'order' => ['x', 'y'],
                'icons' => ['x' => 'i'],
                'tints' => ['x' => 2],
                'tile_limit' => 3,
                'featured_only' => true,
            ]),
        ]);

        self::assertSame(['a', 'b'], $form['vendor_codes']);
        self::assertSame('5', $form['category_id']);
        self::assertSame("x\ny", $form['chip_order']);
        self::assertSame('x=i', $form['chip_icons']);
        self::assertSame('x=2', $form['chip_tints']);
        self::assertSame('3', $form['tile_limit']);
        self::assertSame('1', $form['featured_only']);
    }
}
