<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Home;

use MagentoEgypt\HubApp\Model\Home\TitleResolver;
use PHPUnit\Framework\TestCase;

/**
 * Section titles: store language, type default, "-" hides, never the other language.
 * (__() without a translation returns the source text in unit tests.)
 */
final class TitleResolverTest extends TestCase
{
    private TitleResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TitleResolver();
    }

    public function testTitleInTheStoreLanguage(): void
    {
        $row = ['type' => 'TODAYS_DEALS', 'title_en' => 'Deals', 'title_ar' => 'عروض'];

        self::assertSame('Deals', $this->resolver->title($row, false));
        self::assertSame('عروض', $this->resolver->title($row, true));
    }

    public function testEmptyTitleFallsBackToTheTypeDefault(): void
    {
        self::assertSame("Today's Deals", $this->resolver->title(['type' => 'TODAYS_DEALS', 'title_en' => ''], false));
    }

    public function testArabicNeverFallsBackToTheEnglishTitle(): void
    {
        $row = ['type' => 'BEST_SELLERS', 'title_en' => 'Our best', 'title_ar' => ''];

        self::assertSame('Best Selling Items', $this->resolver->title($row, true));
    }

    public function testDashHidesTheHeaderEvenWithADefault(): void
    {
        self::assertNull($this->resolver->title(['type' => 'TODAYS_DEALS', 'title_en' => ' - '], false));
    }

    public function testProviderDefaultWhenTheTypeHasNone(): void
    {
        self::assertSame('Clothes', $this->resolver->title(['type' => 'CATEGORY_RAIL'], false, ' Clothes '));
        self::assertNull($this->resolver->title(['type' => 'CMS_PROMOS'], false));
        self::assertNull($this->resolver->title(['type' => 'CATEGORY_RAIL'], false, '  '));
    }

    public function testSubtitle(): void
    {
        self::assertSame('Fresh', $this->resolver->subtitle(['subtitle_en' => ' Fresh '], false));
        self::assertSame('طازج', $this->resolver->subtitle(['subtitle_en' => 'Fresh', 'subtitle_ar' => 'طازج'], true));
        self::assertNull($this->resolver->subtitle(['subtitle_en' => 'Fresh'], true));
        self::assertNull($this->resolver->subtitle(['subtitle_en' => '-'], false));
    }

    public function testBadgeComesFromTheOptionsInTheStoreLanguage(): void
    {
        $row = ['options' => json_encode(['badge_en' => 'AI ENGINE', 'badge_ar' => 'محرك ذكي'])];
        self::assertSame('AI ENGINE', $this->resolver->badge($row, false));
        self::assertSame('محرك ذكي', $this->resolver->badge($row, true));
        self::assertNull($this->resolver->badge(['options' => json_encode(['badge_en' => 'AI ENGINE'])], true));
        self::assertNull($this->resolver->badge(['options' => null], false));
    }

    public function testArabicLocales(): void
    {
        self::assertTrue(TitleResolver::isArabic('ar_SA'));
        self::assertTrue(TitleResolver::isArabic('AR_eg'));
        self::assertFalse(TitleResolver::isArabic('en_US'));
    }
}
