<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Test\Unit\Model\Otp;

use MagentoEgypt\SmsExtend\Model\Otp\MobileNumber;
use MagentoEgypt\SmsExtend\Test\Unit\OtpWithMemoryCache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Egyptian and UAE numbers for WhatsApp codes, and the property that keeps codes working: a code sent
 * to the matched account's stored number is filed under the key the typed number verifies with.
 */
class MobileNumberTest extends TestCase
{
    /** Spellings found in customer_entity.mobilenumber, old (Egypt, "+...") and new (UAE), and junk. */
    private const STORED = [
        '+201001234567', '201001234567', '01001234567', '1001234567', '0201001234567', '+0201001234567',
        '+01001234567', '+1001234567',
        '+971501234567', '971501234567', '00971501234567', '+00971501234567', '0501234567', '+0501234567',
        '501234567',
        '+501234567', '+508412345', '508412345', '+971508412345', '+447911123456', '447911123456', '+16505551234',
        '', 'n/a', '+0447911123456', '00447911123456', '0971501234567', '5012345678',
    ];

    /** What people type: every stored spelling, and the same numbers with separators. */
    private const TYPED_EXTRA = [
        '+971 50 123 4567', '050-123-4567', '(050) 1234567', '971 50 123 4567', '050 841 2345',
        '+20 100 123 4567', '0100 123 4567', ' 01001234567 ', '+44 7911 123456', '+1 (650) 555-1234',
    ];

    /**
     * @return array<string, array{string, ?string}>
     */
    public static function canonicalNumbers(): array
    {
        return [
            'UAE E.164' => ['+971501234567', '+971501234567'],
            'UAE E.164 with spaces' => ['+971 50 123 4567', '+971501234567'],
            'UAE without +' => ['971501234567', '+971501234567'],
            'UAE 00' => ['00971501234567', '+971501234567'],
            'UAE national' => ['0501234567', '+971501234567'],
            'UAE national with separators' => ['050-123-4567', '+971501234567'],
            'UAE 9 digits' => ['501234567', '+971501234567'],
            'UAE national typed with +' => ['+0501234567', '+971501234567'],
            'UAE 00 typed with +' => ['+00971501234567', '+971501234567'],
            'Egypt national' => ['01001234567', '+201001234567'],
            'Egypt bare' => ['1001234567', '+201001234567'],
            'Egypt 20...' => ['201001234567', '+201001234567'],
            'Egypt 020...' => ['0201001234567', '+201001234567'],
            'Egypt E.164' => ['+201001234567', '+201001234567'],
            'Egypt national typed with +' => ['+01001234567', '+201001234567'],
            '+ then 9 digits is dialled as written' => ['+501234567', '+501234567'],
            'St Pierre (+508) stays +508' => ['+508412345', '+508412345'],
            'UK' => ['+447911123456', '+447911123456'],
            'US with separators' => ['+1 (650) 555-1234', '+16505551234'],
            'bare foreign: never guessed' => ['447911123456', null],
            '10 digits starting 5: not UAE' => ['5012345678', null],
            '0 + 9715...: not a spelling' => ['0971501234567', null],
            'empty' => ['', null],
            'blank' => ['   ', null],
            'no digits' => ['n/a', null],
        ];
    }

    #[DataProvider('canonicalNumbers')]
    public function testCanonical(string $raw, ?string $expected): void
    {
        self::assertSame($expected, MobileNumber::canonical($raw));
        if ($expected !== null) {
            self::assertSame($expected, MobileNumber::canonical($expected), 'canonical() of its own result');
        }
    }

    public function testKeyIsTheCanonicalDigitsElseTheOwnDigits(): void
    {
        self::assertSame('971501234567', MobileNumber::key('0501234567'));
        self::assertSame('971501234567', MobileNumber::key('+971 50 123 4567'));
        self::assertSame('201001234567', MobileNumber::key('01001234567'));
        self::assertSame('501234567', MobileNumber::key('+501234567'));
        self::assertSame('447911123456', MobileNumber::key('447911123456'));
        self::assertSame('', MobileNumber::key(''));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function sameNumber(): array
    {
        return [
            'UAE national typed, E.164 stored' => ['0501234567', '+971501234567'],
            'UAE national typed, 971 stored' => ['0501234567', '971501234567'],
            'UAE national typed, 00971 stored' => ['0501234567', '00971501234567'],
            'UAE national typed, 9 digits stored' => ['0501234567', '501234567'],
            'UAE E.164 typed, national stored' => ['+971501234567', '0501234567'],
            'UAE E.164 typed, 9 digits stored' => ['+971501234567', '501234567'],
            'UAE spaces typed' => ['+971 50 123 4567', '0501234567'],
            'UAE dashes typed' => ['050-123-4567', '+971501234567'],
            'UAE 9 digits typed' => ['501234567', '+971501234567'],
            'UAE "+0..." typed' => ['+0501234567', '0501234567'],
            'UAE 00 typed' => ['00971501234567', '0501234567'],
            'Egypt national typed, E.164 stored' => ['01001234567', '+201001234567'],
            'Egypt E.164 typed, national stored' => ['+201001234567', '01001234567'],
            'Egypt spaces typed' => ['+20 100 123 4567', '201001234567'],
            'Egypt bare typed' => ['1001234567', '01001234567'],
            'Egypt "+0..." typed' => ['+01001234567', '01001234567'],
            'Egypt 020 stored (matched before as a MySQL number)' => ['01001234567', '0201001234567'],
            'foreign E.164 typed, bare stored' => ['+447911123456', '447911123456'],
            'foreign bare typed, E.164 stored' => ['447911123456', '+447911123456'],
            'foreign exact' => ['+508412345', '+508412345'],
        ];
    }

    #[DataProvider('sameNumber')]
    public function testSpellingsOfOneNumberMatch(string $typed, string $stored): void
    {
        self::assertContains($stored, MobileNumber::candidates($typed));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function differentNumbers(): array
    {
        return [
            //  The +501 (Belize) case of P2 design 0.6: a bare UAE mobile is not the typed "+5...".
            'Belize spelling of a bare UAE mobile' => ['+501234567', '501234567'],
            'bare UAE mobile typed, "+5..." stored' => ['501234567', '+501234567'],
            'St Pierre (+508) and a bare UAE mobile' => ['508412345', '+508412345'],
            'St Pierre typed, bare UAE stored' => ['+508412345', '508412345'],
            'UAE and a "+0..." foreign mangle' => ['0501234567', '+0447911123456'],
            'UAE and Egypt' => ['0501234567', '01001234567'],
        ];
    }

    #[DataProvider('differentNumbers')]
    public function testSpellingsOfDifferentNumbersNeverMatch(string $typed, string $stored): void
    {
        self::assertNotContains($stored, MobileNumber::candidates($typed));
    }

    public function testCandidatesAreStringsSoMySqlComparesThemAsStrings(): void
    {
        //  An int is quoted unquoted, and MySQL then compares the column as a number: the int 501234567
        //  matched "+501234567" and "0501234567" alike.
        foreach (array_merge(self::STORED, self::TYPED_EXTRA) as $typed) {
            foreach (MobileNumber::candidates($typed) as $candidate) {
                self::assertSame('string', gettype($candidate), 'candidate of ' . var_export($typed, true));
            }
        }
    }

    /**
     * The proof asked for: for every typed spelling and every stored spelling it matches (the database
     * compares the candidate strings exactly), a code sent to the stored number's canonical form is filed
     * under the key the typed number verifies with. Run on the pure functions, and on the real Otp
     * helper's own cache path (sendOtp()/getOtp() file the code under the delivery number, verifyOtp()
     * reads it with the typed number).
     */
    public function testEveryMatchedAccountVerifiesWithTheTypedNumber(): void
    {
        $otp = new OtpWithMemoryCache();
        $pairs = 0;
        foreach (array_merge(self::STORED, self::TYPED_EXTRA) as $typed) {
            $candidates = MobileNumber::candidates($typed);
            foreach (self::STORED as $stored) {
                if (!in_array($stored, $candidates, true)) {
                    continue;
                }
                $delivery = MobileNumber::canonical($stored);
                if ($delivery === null) {
                    continue;
                }
                $label = var_export($typed, true) . ' -> stored ' . var_export($stored, true);
                self::assertSame(MobileNumber::key($typed), MobileNumber::key($delivery), $label);

                $code = (string) $otp->getOtp($delivery);
                self::assertTrue($otp->verifyOtp($typed, $code), $label);
                $pairs++;
            }
        }
        //  Not vacuous: every spelling of the three deliverable numbers above reaches the others.
        self::assertGreaterThan(150, $pairs);
    }

    public function testACodeFiledForOneNumberIsNeverReadForAnother(): void
    {
        $otp = new OtpWithMemoryCache();
        $code = (string) $otp->getOtp('+971508412345');

        foreach (['+508412345', '+9715084123450', '+971508412346', '0508412346'] as $other) {
            try {
                $otp->verifyOtp($other, $code);
                self::fail('code read for ' . $other);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                self::assertSame('OTP has expired or does not exist.', $e->getMessage());
            }
        }
        //  Still there for its own number, in any spelling.
        self::assertTrue($otp->verifyOtp('050 841 2345', $code));
    }
}
