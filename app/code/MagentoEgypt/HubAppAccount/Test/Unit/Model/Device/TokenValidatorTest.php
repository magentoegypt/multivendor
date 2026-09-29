<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\Device;

use MagentoEgypt\HubAppAccount\Model\Device\TokenValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TokenValidatorTest extends TestCase
{
    private const FCM = 'dQw4w9WgXcQ:APA91bHun4MxP5egoKMwt2KZFBaFUH-1RYqx6mrLu_d2wmQ3PpNXJ-5Sb_sxGd1CxE0y.z';

    /**
     * @return array<string, array{string, bool}>
     */
    public static function tokens(): array
    {
        return [
            'real FCM shape' => [self::FCM, true],
            'exactly 20 characters' => [str_repeat('a', 20), true],
            'exactly 500 characters' => [str_repeat('A', 500), true],
            '19 characters' => [str_repeat('a', 19), false],
            '501 characters' => [str_repeat('A', 501), false],
            'space inside' => ['abcdefghij klmnopqrstu', false],
            'slash' => ['abcdefghij/klmnopqrstu', false],
            'quote (SQL)' => ["abcdefghij'klmnopqrstu", false],
            'angle bracket' => ['abcdefghij<klmnopqrstu', false],
            'empty' => ['', false],
        ];
    }

    #[DataProvider('tokens')]
    public function testToken(string $token, bool $valid): void
    {
        self::assertSame($valid, (new TokenValidator())->isValidToken($token));
    }

    public function testPlatformMapsToTheTableValues(): void
    {
        $validator = new TokenValidator();
        self::assertSame('android', $validator->platform('ANDROID'));
        self::assertSame('ios', $validator->platform('IOS'));
        self::assertNull($validator->platform('WEB'));
    }

    public function testRegistrationIsNormalised(): void
    {
        self::assertSame(
            ['token' => self::FCM, 'platform' => 'ios', 'app_version' => '1.4.0+37'],
            (new TokenValidator())->registration(['token' => '  ' . self::FCM . ' ', 'platform' => 'IOS', 'app_version' => ' 1.4.0+37 '])
        );
        self::assertSame(
            ['token' => self::FCM, 'platform' => 'android', 'app_version' => null],
            (new TokenValidator())->registration(['token' => self::FCM, 'platform' => 'ANDROID', 'app_version' => ''])
        );
        self::assertSame(
            '1.4.0 (37)',
            (new TokenValidator())->registration(['token' => self::FCM, 'platform' => 'ANDROID', 'app_version' => '1.4.0 (37)'])['app_version'] ?? null
        );
    }

    public function testInvalidRegistrationsAreRefused(): void
    {
        $validator = new TokenValidator();
        self::assertNull($validator->registration(['token' => 'short', 'platform' => 'IOS']));
        self::assertNull($validator->registration(['token' => self::FCM, 'platform' => 'WEB']));
        self::assertNull($validator->registration(['token' => self::FCM, 'platform' => 'IOS', 'app_version' => str_repeat('1', 33)]));
        self::assertNull($validator->registration(['token' => self::FCM, 'platform' => 'IOS', 'app_version' => '<script>']));
        self::assertNull($validator->registration(['token' => self::FCM]));
        self::assertNull($validator->unregistration(['token' => 'x']));
        self::assertSame(self::FCM, $validator->unregistration(['token' => self::FCM]));
    }
}
