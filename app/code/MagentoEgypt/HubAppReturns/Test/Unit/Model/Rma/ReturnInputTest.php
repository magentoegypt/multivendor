<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use MagentoEgypt\HubAppReturns\Model\Rma\ReturnInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * What the app may store in the return fields the admin and seller panels print unescaped.
 */
class ReturnInputTest extends TestCase
{
    /**
     * @return array<string, array{string, bool}>
     */
    public static function reasons(): array
    {
        return [
            'plain' => ['The screen is cracked', true],
            'apostrophe and ampersand' => ['It\'s scratched & dented', true],
            'Arabic' => ['المنتج معطوب', true],
            'digits and punctuation' => ['Size 42 (too small), 2x', true],
            'script tag' => ['<script>alert(1)</script>', false],
            'attribute break-out' => ['x" onmouseover="alert(1)', false],
            'lone less-than' => ['price < paid', false],
            'lone greater-than' => ['a > b', false],
        ];
    }

    #[DataProvider('reasons')]
    public function testReasonRefusesOnlyTheCharactersThatOpenMarkup(string $reason, bool $safe): void
    {
        self::assertSame($safe, ReturnInput::isSafeReason($reason));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function trackingCodes(): array
    {
        return [
            'postal' => ['RR123456789AE', true],
            'with spaces' => ['1Z 999 AA1 01 2345 6784', true],
            'separators' => ['DHL/123-45#6.a_b', true],
            'empty' => ['', false],
            'tag' => ['"><img src=x onerror=alert(1)>', false],
            'apostrophe' => ["AB'12", false],
            'semicolon' => ['AB;12', false],
            'ampersand' => ['AB&amp;12', false],
            'Arabic letters' => ['رقم123', false],
            'trailing newline' => ["AB12\n", false],
            'tab' => ["AB\t12", false],
        ];
    }

    #[DataProvider('trackingCodes')]
    public function testTrackingCodeIsAnAllowList(string $code, bool $valid): void
    {
        self::assertSame($valid, ReturnInput::isTrackingCode($code));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function addresses(): array
    {
        return [
            'forwarded address' => ['203.0.113.7', '10.0.0.1', '203.0.113.7'],
            'forwarded chain kept' => ['203.0.113.7, 10.0.0.2', '10.0.0.1', '203.0.113.7, 10.0.0.2'],
            'markup in the header' => ['<script>alert(1)</script>', '198.51.100.4', '198.51.100.4'],
            'markup mixed into a chain' => ['"><img src=x>, 203.0.113.9', '10.0.0.1', '203.0.113.9'],
            'no header, IPv6 connection' => ['', '2001:db8::1', '2001:db8::1'],
            'nothing valid' => ['junk', 'also junk', ''],
        ];
    }

    #[DataProvider('addresses')]
    public function testOnlyValidAddressesAreStored(string $reported, string $remote, string $expected): void
    {
        self::assertSame($expected, ReturnInput::clientIp($reported, $remote));
    }
}
