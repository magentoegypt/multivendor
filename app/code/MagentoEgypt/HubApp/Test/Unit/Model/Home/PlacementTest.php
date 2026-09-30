<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Home;

use MagentoEgypt\HubApp\Model\Home\Provider\PlacementProvider;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;
use MagentoEgypt\HubApp\Model\Source\SectionType;
use PHPUnit\Framework\TestCase;

/**
 * ACTIVE_ORDER: a section that only says where the app draws the customer's own
 * open order. It has no content and must never be dropped as "empty".
 */
final class PlacementTest extends TestCase
{
    public function testAPlacementIsNeverEmptyAndKeepsTheFlagThroughEveryChange(): void
    {
        $placement = SectionResult::placement();

        self::assertTrue($placement->isPlacement());
        self::assertFalse($placement->isEmpty());
        self::assertSame([], $placement->getFields());
        self::assertSame([], $placement->getProductIds());

        $changed = $placement->withTags(['hm_app_home'])->withDefaultTitle('x')->withDefaultMoreLink(null)->withField('a', null);
        self::assertTrue($changed->isPlacement());
        self::assertFalse($changed->isEmpty());

        self::assertFalse(SectionResult::create()->isPlacement());
        self::assertTrue(SectionResult::create()->isEmpty(), 'an ordinary section with nothing in it is still omitted');
    }

    public function testTheProviderPlacesActiveOrderOnly(): void
    {
        $provider = new PlacementProvider();

        $result = $provider->provide($this->context('active_order'));
        self::assertNotNull($result);
        self::assertTrue($result->isPlacement());

        self::assertNull($provider->provide($this->context('TRUST_ROW')));
    }

    public function testActiveOrderIsAKnownTypeWithAnAdminLabelAndNoContentSource(): void
    {
        self::assertTrue(SectionType::isKnown('ACTIVE_ORDER'));
        self::assertTrue(SectionType::isKnown('active_order'));
        self::assertSame([SectionType::ACTIVE_ORDER], SectionType::PLACEMENT_TYPES);

        foreach (SectionType::PLACEMENT_TYPES as $type) {
            self::assertContains($type, SectionType::ALL);
            self::assertNotContains($type, SectionType::PRODUCT_TYPES);
            self::assertNotContains($type, SectionType::STORE_TYPES);
            self::assertArrayNotHasKey($type, SectionType::CMS_DEFAULTS);
        }

        $values = array_column((new SectionType())->toOptionArray(), 'value');
        self::assertSame(SectionType::ALL, $values, 'every type has an admin option, in the list order');
        self::assertSame(SectionType::ACTIVE_ORDER, $values[1], 'offered right after the delivery strip, where Figma 07 draws the card');
    }

    /**
     * The admin list and the GraphQL enum are the same set: a type the enum lacks
     * would fail the whole hmAppHome response.
     */
    public function testTheTypeListMatchesTheGraphQlEnum(): void
    {
        $sdl = (string) file_get_contents(dirname(__DIR__, 4) . '/etc/schema.graphqls');
        self::assertSame(1, preg_match('/enum HmSectionType[^{]*\{([^}]*)\}/', $sdl, $match));
        preg_match_all('/^\s*([A-Z_]+)\s+@doc/m', $match[1], $values);

        $enum = $values[1];
        $list = SectionType::ALL;
        sort($enum);
        sort($list);
        self::assertSame($list, $enum);
    }

    private function context(string $type): SectionContext
    {
        return new SectionContext(
            ['section_id' => 9, 'type' => $type],
            1,
            'en',
            1,
            'en_US',
            'CUSTOMER',
            new \DateTimeImmutable('2026-09-30 12:00:00', new \DateTimeZone('UTC')),
            'Asia/Riyadh'
        );
    }
}
