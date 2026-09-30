<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\Otp;

use MagentoEgypt\HubAppAccount\Model\Otp\DeliveryNumber;
use MagentoEgypt\SmsExtend\Helper\Otp;
use MagentoEgypt\SmsExtend\Model\Otp\MobileNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Where a WhatsApp sign-in code may go, with SmsExtend's real canonicaliser.
 */
class DeliveryNumberTest extends TestCase
{
    private DeliveryNumber $delivery;

    protected function setUp(): void
    {
        //  canonicalizeMobileForDelivery() uses none of the helper's injected services.
        $otp = (new \ReflectionClass(Otp::class))->newInstanceWithoutConstructor();
        $this->delivery = new DeliveryNumber($otp);
    }

    public function testBareStoredNumberIsNeverDeliveredToTheBelizeSpelling(): void
    {
        //  A bare "501234567" is a UAE mobile: its code goes to +971501234567, never to +501 (Belize).
        //  (The typed "+501234567" no longer even matches it: SmsExtend's MobileNumberTest.)
        self::assertSame(
            ['reason' => DeliveryNumber::OK, 'number' => '+971501234567', 'customer_id' => 7],
            $this->delivery->resolve([7 => '501234567'])
        );
    }

    /**
     * @return array<string, array{string, ?string}>
     */
    public static function storedNumbers(): array
    {
        return [
            'UAE, international' => ['+971501234567', '+971501234567'],
            'UAE, international with spaces' => ['+971 50 123 4567', '+971501234567'],
            'UAE without "+"' => ['971501234567', '+971501234567'],
            'UAE national' => ['0501234567', '+971501234567'],
            'UAE national with separators' => ['050-123-4567', '+971501234567'],
            'UAE 00' => ['00971501234567', '+971501234567'],
            'UAE 9 digits' => ['501234567', '+971501234567'],
            'bare foreign number: refused, never guessed' => ['447911123456', null],
            'Egypt national' => ['01001234567', '+201001234567'],
            'Egypt bare' => ['1001234567', '+201001234567'],
            'Egypt 20...' => ['201001234567', '+201001234567'],
            'Egypt E.164' => ['+201001234567', '+201001234567'],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('storedNumbers')]
    public function testTheCodeGoesToTheCanonicalStoredNumberOrNowhere(string $stored, ?string $expected): void
    {
        $result = $this->delivery->resolve([12 => $stored]);
        self::assertSame($expected, $result['number']);
        self::assertSame($expected === null ? DeliveryNumber::UNDELIVERABLE : DeliveryNumber::OK, $result['reason']);
        self::assertSame(12, $result['customer_id']);
    }

    public function testNoAccountAndSeveralAccountsSendNothing(): void
    {
        self::assertSame(
            ['reason' => DeliveryNumber::NONE, 'number' => null, 'customer_id' => null],
            $this->delivery->resolve([])
        );
        self::assertSame(
            ['reason' => DeliveryNumber::AMBIGUOUS, 'number' => null, 'customer_id' => null],
            $this->delivery->resolve([3 => '+971501234567', 4 => '971501234567'])
        );
    }

    /**
     * Typed spellings that match a stored number (Otp::normalizeMobileCandidates), and that number.
     *
     * @return array<string, array{string, string}>
     */
    public static function matchingSpellings(): array
    {
        return [
            'Egypt national typed, E.164 stored' => ['01001234567', '+201001234567'],
            'E.164 typed, national stored' => ['+201001234567', '01001234567'],
            'spaces typed' => ['+20 100 123 4567', '201001234567'],
            'UAE without "+" typed' => ['971501234567', '+971501234567'],
            'UAE with spaces typed' => ['+971 50 123 4567', '+971501234567'],
            '"+0..." typed, national stored' => ['+01001234567', '01001234567'],
            'UAE national typed, E.164 stored' => ['0501234567', '+971501234567'],
            'UAE E.164 typed, national stored' => ['+971501234567', '0501234567'],
            'UAE 9 digits typed, 00971 stored' => ['501234567', '00971501234567'],
            'UAE 971 typed, 9 digits stored' => ['971501234567', '501234567'],
            'UAE "+0..." typed, national stored' => ['+0501234567', '0501234567'],
        ];
    }

    #[DataProvider('matchingSpellings')]
    public function testCodeIsFiledUnderTheKeyTheTypedNumberVerifiesWith(string $typed, string $stored): void
    {
        //  Otp::sendOtp(<delivery>) files the code under the delivery number's key; verifyOtp(<typed>)
        //  looks under the typed number's (MobileNumber::key, the helper's cache key). They must agree, or
        //  nobody could sign in. SmsExtend's MobileNumberTest proves it for every matching spelling.
        $delivery = $this->delivery->resolve([1 => $stored])['number'];
        self::assertNotNull($delivery);
        self::assertContains($stored, MobileNumber::candidates($typed), 'the typed number finds the account');
        self::assertSame(MobileNumber::key($typed), MobileNumber::key((string) $delivery));
    }

    public function testLimitsCountEverySpellingAsOneNumber(): void
    {
        //  SmsExtend's OtpGuard counts sends and wrong codes per MobileNumber::key.
        self::assertSame('201001234567', MobileNumber::key('01001234567'));
        self::assertSame('201001234567', MobileNumber::key('+20 100 123 4567'));
        self::assertSame('971501234567', MobileNumber::key('+971501234567'));
        self::assertSame('971501234567', MobileNumber::key('971501234567'));
        self::assertSame('971501234567', MobileNumber::key('0501234567'));
        self::assertSame('971501234567', MobileNumber::key('501234567'));
        self::assertSame('501234567', MobileNumber::key('+501234567'));
    }
}
