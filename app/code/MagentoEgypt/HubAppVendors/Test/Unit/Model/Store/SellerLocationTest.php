<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use MagentoEgypt\HubAppVendors\Model\Store\SellerLocation;
use PHPUnit\Framework\TestCase;

/**
 * The location line: Vnecoms' template filling, then the theme's city/region translation and seams.
 */
final class SellerLocationTest extends TestCase
{
    private const VARIABLES = [
        'street' => '12 Al Wasl Rd',
        'city' => 'دبي',
        'country' => 'United Arab Emirates',
        'region' => 'دبي',
        'postcode' => '',
    ];

    public function testTheDefaultTemplateIsRegionThenCountry(): void
    {
        self::assertSame(
            'دبي, United Arab Emirates',
            SellerLocation::compose('{{var region}}, {{var country}}', self::VARIABLES)
        );
    }

    public function testEmptyPartsLeaveNoStrayCommasAtTheEnds(): void
    {
        self::assertSame(
            'United Arab Emirates',
            SellerLocation::compose('{{var region}}, {{var country}}', ['region' => ''] + self::VARIABLES)
        );
        self::assertSame(
            '12 Al Wasl Rd, دبي',
            SellerLocation::compose('{{ var street }}, {{var city}}, {{var postcode}}', self::VARIABLES)
        );
        //  Only var directives exist in the admin's list of variables; anything else prints nothing.
        self::assertSame('', SellerLocation::compose('{{depend street}}{{var unknown}}', self::VARIABLES));
    }

    public function testCityAndRegionAreTranslatedLongestFirstAndSeamsTidied(): void
    {
        $translations = ['القاهرة' => 'Cairo', 'القاهرة الجديدة' => 'New Cairo'];
        $translate = static fn (string $text): string => $translations[$text] ?? $text;

        //  A trailing space typed into the city rendered "Cairo , Egypt" before the seam fix.
        self::assertSame(
            'Cairo, Egypt',
            SellerLocation::localise('القاهرة , Egypt', ['القاهرة ', 'القاهرة'], $translate)
        );
        //  The longer token goes first, so "New Cairo" is not "New القاهرة" half-translated.
        self::assertSame(
            'New Cairo, Cairo, Egypt',
            SellerLocation::localise('القاهرة الجديدة, القاهرة, Egypt', ['القاهرة', 'القاهرة الجديدة'], $translate)
        );
        //  No translation (the Arabic store): the seller's own words, double spaces collapsed.
        self::assertSame('دبي, الإمارات', SellerLocation::localise('دبي,  الإمارات', ['دبي'], $translate));
    }
}
