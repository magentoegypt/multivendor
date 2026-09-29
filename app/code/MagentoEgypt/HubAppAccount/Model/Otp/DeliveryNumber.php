<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Otp;

use MagentoEgypt\SmsExtend\Helper\Otp;

/**
 * Where a sign-in code may go: the number STORED on the one matching account, in its canonical form,
 * or nowhere (P2 design 0.6 / 9.5).
 *
 * The typed number only finds accounts (Otp::getCustomersByMobile matches every stored spelling of it).
 * Sending to the typed spelling itself let a spelling that matched an account without being its
 * number receive that account's code: a number stored bare as "501234567" matched "+501234567", which
 * is a Belize (+501) number. So:
 *   - no account            -> NONE, nothing sent;
 *   - several accounts      -> AMBIGUOUS, nothing sent (verifyOtp never signs anyone in on those);
 *   - stored number without a canonical form (not Egyptian, not a UAE mobile spelling, not
 *     "+<country code>...", SmsExtend's MobileNumber::canonical())
 *                           -> UNDELIVERABLE, nothing sent: refused, never guessed;
 *   - otherwise             -> OK, Otp::canonicalizeMobileForDelivery(<stored number>): a stored UAE
 *                              "0501234567", "501234567", "971501234567" or "00971501234567" gets
 *                              the code on +971501234567.
 */
class DeliveryNumber
{
    public const OK = 'ok';
    public const NONE = 'none';
    public const AMBIGUOUS = 'ambiguous';
    public const UNDELIVERABLE = 'undeliverable';

    public function __construct(
        private readonly Otp $otp
    ) {
    }

    /**
     * @param array<int, string|null> $storedNumbers customer id => mobilenumber stored on that account,
     *     for every account the typed number matched
     * @return array{reason: string, number: ?string, customer_id: ?int}
     */
    public function resolve(array $storedNumbers): array
    {
        if (!$storedNumbers) {
            return ['reason' => self::NONE, 'number' => null, 'customer_id' => null];
        }
        if (count($storedNumbers) > 1) {
            return ['reason' => self::AMBIGUOUS, 'number' => null, 'customer_id' => null];
        }
        $customerId = (int) array_key_first($storedNumbers);
        $number = $this->otp->canonicalizeMobileForDelivery(trim((string) reset($storedNumbers)));
        if ($number === null) {
            return ['reason' => self::UNDELIVERABLE, 'number' => null, 'customer_id' => $customerId];
        }

        return ['reason' => self::OK, 'number' => $number, 'customer_id' => $customerId];
    }
}
