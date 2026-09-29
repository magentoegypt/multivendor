<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Model\Otp;

/**
 * What a mobile number means to the WhatsApp codes: where a code for it is delivered (canonical), the
 * digits its code, attempts and cooldown are filed under (key), and which stored spellings of
 * customer_entity.mobilenumber are the same number (candidates). Otp (the helper) delegates to this
 * class; so do the website's mobile sign-in, the REST service the seller app uses and the app's GraphQL.
 *
 * canonical() understands:
 *  - Egypt (+20, 10-digit national numbers starting with 1): 1XXXXXXXXX, 01XXXXXXXXX, 201XXXXXXXXX,
 *    0201XXXXXXXXX, with or without "+" (unchanged);
 *  - UAE mobiles (+971, 9-digit national numbers starting with 5): 5XXXXXXXX, 05XXXXXXXX,
 *    9715XXXXXXXX, 009715XXXXXXXX, all to +9715XXXXXXXX. With a "+" only after a "0" ("+05...",
 *    "+009715..."): no country code starts with 0, so such a "+" never dials anything else;
 *  - anything else written "+<country code>...": taken as dialled, e.g. "+501234567" stays +501...
 *    and is never read as a UAE number;
 *  - anything else: null, and nothing is sent (never guessed).
 *
 * The rule that keeps codes working and keeps them apart: every stored spelling candidates() returns for
 * a typed number has that typed number's key. A code sent to canonical(<stored number>) is filed under
 * key(<stored number>), and verifying with the typed number reads key(<typed number>): the same digits
 * for every account a typed number can reach, and a code filed for one number is never read for a
 * spelling of another (the stored bare "501234567", a UAE mobile, is not the typed "+501234567").
 *
 * candidates() returns strings: Magento quotes a PHP int unquoted, and MySQL then compares the column
 * as a number, which matched spellings of other numbers ("+501234567" for the int 501234567).
 */
final class MobileNumber
{
    private const EGYPT = '20';
    private const UAE = '971';

    private function __construct()
    {
    }

    /**
     * The number in international form ("+<digits>") where a code for it is delivered, or null.
     */
    public static function canonical(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $egypt = self::egyptian($raw);
        if ($egypt !== null) {
            return $egypt;
        }
        $digits = self::digits($raw);
        if ($digits === '') {
            return null;
        }
        $plus = $raw[0] === '+';
        $uae = self::uae($digits, $plus);
        if ($uae !== null) {
            return $uae;
        }

        return $plus ? '+' . $digits : null;
    }

    /**
     * The digits a number's code, attempt counter and resend cooldown are filed under: the canonical
     * number's, else the number's own.
     */
    public static function key(string $raw): string
    {
        return self::digits(self::canonical($raw) ?? $raw);
    }

    /**
     * The stored spellings that are this number: every one has this number's key.
     *
     * @return string[]
     */
    public static function candidates(string $input): array
    {
        $input = trim($input);
        if (self::digits($input) === '') {
            return [];
        }
        $key = self::key($input);
        $out = [];
        foreach (array_merge(self::legacyCandidates($input), self::spellings(self::canonical($input))) as $candidate) {
            if (self::key($candidate) === $key && !in_array($candidate, $out, true)) {
                $out[] = $candidate;
            }
        }

        return $out;
    }

    /**
     * Egyptian E.164 (+201XXXXXXXXX) of a value, or null when it is not an Egyptian mobile number
     * (national = 10 digits starting with 1).
     */
    public static function egyptian(string $raw): ?string
    {
        $digits = self::digits($raw);
        if ($digits === '') {
            return null;
        }

        $national = $digits;
        if (strlen($digits) === 12 && str_starts_with($digits, self::EGYPT)) {
            $national = substr($digits, 2);            // 20XXXXXXXXXX
        } elseif (strlen($digits) === 13 && str_starts_with($digits, '0' . self::EGYPT)) {
            $national = substr($digits, 3);            // 020XXXXXXXXXX
        } elseif (strlen($digits) === 11 && $digits[0] === '0') {
            $national = substr($digits, 1);            // 0XXXXXXXXXX
        }

        return strlen($national) === 10 && $national[0] === '1' ? '+' . self::EGYPT . $national : null;
    }

    /**
     * UAE E.164 (+9715XXXXXXXX) of a UAE mobile spelling, or null.
     */
    private static function uae(string $digits, bool $plus): ?string
    {
        if ($plus && $digits[0] !== '0') {
            return null;                                // "+<country code>...": dialled as written
        }
        if (strlen($digits) === 9 && $digits[0] === '5') {
            $national = $digits;                        // 5XXXXXXXX
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '05')) {
            $national = substr($digits, 1);             // 05XXXXXXXX
        } elseif (strlen($digits) === 12 && str_starts_with($digits, self::UAE . '5')) {
            $national = substr($digits, 3);             // 9715XXXXXXXX
        } elseif (strlen($digits) === 14 && str_starts_with($digits, '00' . self::UAE . '5')) {
            $national = substr($digits, 5);             // 009715XXXXXXXX
        } else {
            return null;
        }

        return '+' . self::UAE . $national;
    }

    /**
     * The stored spellings an Egyptian or UAE number is known in, for a canonical number.
     *
     * @return string[]
     */
    private static function spellings(?string $canonical): array
    {
        $digits = self::digits((string) $canonical);
        if (strlen($digits) === 12 && str_starts_with($digits, self::EGYPT . '1')) {
            $n = substr($digits, 2);
            //  "020...", "+020...", "+0..." and "+1..." were matched before only as MySQL numbers.
            return ['+20' . $n, '20' . $n, '0' . $n, $n, '020' . $n, '+020' . $n, '+0' . $n, '+' . $n];
        }
        if (strlen($digits) === 12 && str_starts_with($digits, self::UAE . '5')) {
            $n = substr($digits, 3);

            return ['+971' . $n, '971' . $n, '00971' . $n, '+00971' . $n, '0' . $n, '+0' . $n, $n];
        }

        return [];
    }

    /**
     * The candidates Otp::normalizeMobileCandidates() built before UAE numbers were understood, as
     * strings; candidates() keeps those that have the typed number's key.
     *
     * @return string[]
     */
    private static function legacyCandidates(string $input): array
    {
        $digits = self::digits($input);
        if ($digits === '') {
            return [];
        }
        //  Explicit foreign E.164 ("+", but not Egypt).
        if ($input[0] === '+' && !str_starts_with($digits, self::EGYPT)) {
            return ['+' . $digits, $digits];
        }
        $egypt = self::egyptian($input);
        if ($egypt !== null) {
            $national = substr($egypt, 3);

            return ['+20' . $national, '20' . $national, '0' . $national, $national];
        }

        return [$digits, '+' . $digits];
    }

    private static function digits(string $value): string
    {
        return (string) preg_replace('/\D+/', '', $value);
    }
}
