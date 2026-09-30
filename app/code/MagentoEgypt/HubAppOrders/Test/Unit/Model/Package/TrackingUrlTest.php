<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Test\Unit\Model\Package;

use MagentoEgypt\HubAppOrders\Model\Package\TrackingUrl;
use PHPUnit\Framework\TestCase;

/**
 * "Track parcel" only when a carrier page can be built from the code; custom carriers never.
 */
final class TrackingUrlTest extends TestCase
{
    private const TEMPLATES = [
        'dhl' => 'https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id=%s',
        'ups' => 'https://www.ups.com/track?tracknum=%s',
        'plain' => 'http://insecure.example/%s',
        'twice' => 'https://twice.example/%s/%s',
        'encoded' => 'https://encoded.example/a%20b?n=%s',
    ];

    public function testAKnownCarrierGetsItsPageWithTheNumberEncoded(): void
    {
        $url = new TrackingUrl(self::TEMPLATES);

        self::assertSame(
            'https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id=1234567890',
            $url->forTrack('dhl', ' 1234567890 ')
        );
        self::assertSame('https://www.ups.com/track?tracknum=1Z%20999%2FAA', $url->forTrack('UPS', '1Z 999/AA'));
    }

    public function testCustomAndUnknownCarriersHaveNoPage(): void
    {
        $url = new TrackingUrl(self::TEMPLATES);

        self::assertNull($url->forTrack('custom', '3345 1182'));
        self::assertNull($url->forTrack('aramex', '3345 1182'));
        self::assertNull($url->forTrack('', '3345 1182'));
        self::assertNull($url->forTrack('dhl', '  '));
    }

    public function testOnlyHttpsTemplatesWithOneNumberSlot(): void
    {
        $url = new TrackingUrl(self::TEMPLATES);

        self::assertNull($url->forTrack('plain', '1'));
        self::assertNull($url->forTrack('twice', '1'));
        //  Other percent-encoded characters of a template are kept as they are.
        self::assertSame('https://encoded.example/a%20b?n=7', $url->forTrack('encoded', '7'));
    }

    public function testTheShippedTemplatesAreHttpsWithOneSlot(): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 4) . '/etc/di.xml');
        self::assertNotFalse($xml);
        $items = $xml->xpath('//argument[@name="templates"]/item');
        self::assertNotEmpty($items);
        $templates = [];
        foreach ($items as $item) {
            $templates[(string) $item['name']] = (string) $item;
        }
        self::assertSame(['dhl', 'fedex', 'ups', 'usps'], array_keys($templates));

        $url = new TrackingUrl($templates);
        foreach (array_keys($templates) as $code) {
            $link = $url->forTrack($code, 'AB 12');
            self::assertNotNull($link, $code);
            self::assertStringStartsWith('https://', $link);
            self::assertStringContainsString('AB%2012', $link);
        }
    }
}
