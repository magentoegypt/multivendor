<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Home;

use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;
use PHPUnit\Framework\TestCase;

/**
 * What providers read from a section row, and what they hand back.
 */
final class SectionContextTest extends TestCase
{
    /**
     * @param array<string, mixed> $row
     */
    private function context(array $row): SectionContext
    {
        return new SectionContext(
            $row,
            1,
            'en',
            1,
            'en_US',
            'GUEST',
            new \DateTimeImmutable('2026-09-29 12:00:00', new \DateTimeZone('UTC')),
            'Asia/Riyadh'
        );
    }

    public function testRowIsNormalised(): void
    {
        $context = $this->context([
            'section_id' => '7',
            'type' => 'featured_stores',
            'item_limit' => '0',
            'vendor_codes' => 'ENARA, ronza,,loly ENARA',
            'product_skus' => "test new bundle\nA-1, B 2",
            'options' => '{"order":["a","b"],"tile_limit":2}',
            'category_id' => '0',
            'sort_by' => ' top_rated ',
            'cms_identifier' => ' ',
        ]);

        self::assertSame(7, $context->getSectionId());
        self::assertSame('FEATURED_STORES', $context->getType());
        self::assertSame(8, $context->getLimit(), 'unset limit');
        self::assertSame(['ENARA', 'ronza', 'loly'], $context->getVendorCodes());
        self::assertSame(['test new bundle', 'A-1', 'B 2'], $context->getProductSkus(), 'SKUs keep their spaces');
        self::assertSame(['a', 'b'], $context->getOption('order'));
        self::assertSame(2, $context->getOption('tile_limit'));
        self::assertNull($context->getOption('missing'));
        self::assertNull($context->getCategoryId());
        self::assertSame('TOP_RATED', $context->getSortBy());
        self::assertNull($context->getCmsIdentifier());
        self::assertFalse($context->isArabic());
    }

    public function testLimitIsCapped(): void
    {
        self::assertSame(SectionContext::MAX_LIMIT, $this->context(['item_limit' => 500])->getLimit());
    }

    public function testBrokenOptionsAreEmpty(): void
    {
        self::assertSame([], SectionContext::decodeOptions('{not json'));
        self::assertSame([], SectionContext::decodeOptions('"a string"'));
        self::assertSame(['x' => 1], SectionContext::decodeOptions(['x' => 1]));
    }

    public function testResultNormalisesIdsAndTags(): void
    {
        $result = SectionResult::create()
            ->withProductIds([3, '3', 0, -1, 5])
            ->withTags(['a', 'a', '', 'b'])
            ->withDefaultTitle('  ');

        self::assertSame([3, 5], $result->getProductIds());
        self::assertSame(['a', 'b'], $result->getTags());
        self::assertNull($result->getDefaultTitle());
    }

    public function testEmptyResults(): void
    {
        self::assertTrue(SectionResult::create()->isEmpty());
        self::assertTrue(SectionResult::create()->withField('banners', [])->isEmpty());
        self::assertTrue(SectionResult::create()->withField('countdown_ends_at', '2026-09-29T23:59:59+03:00')->isEmpty());
        self::assertFalse(SectionResult::create()->withField('cms_block', ['content' => 'x'])->isEmpty());
        self::assertFalse(SectionResult::create()->withProductIds([1])->isEmpty());
    }

    public function testResultIsImmutable(): void
    {
        $empty = SectionResult::create();
        $withField = $empty->withField('brands', [1]);

        self::assertSame([], $empty->getFields());
        self::assertSame(['brands' => [1]], $withField->getFields());
    }
}
