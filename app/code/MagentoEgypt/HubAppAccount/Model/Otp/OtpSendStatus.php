<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Otp;

/**
 * HmOtpSendStatus: what a WhatsApp code request did (hmSendWhatsAppCode.status).
 *
 * Only two answers can tell anything about a number without the admin's say-so
 * (hubapp/otp/reveal_unknown_number):
 *   - THROTTLED: the hourly limits (OtpGuard::sendWait) count every request before any account is looked
 *     up, so they stop numbers with and without an account alike;
 *   - MASKED: every other request, whatever happened (sent, no account, several, an unusable stored
 *     number, the resend cooldown, a gateway error), gets the same answer.
 * With the setting on, MASKED is replaced by what happened: SENT, COOLDOWN (a code went to the number
 * within the resend period and still works; only an account's number can be in it), NO_ACCOUNT, MULTIPLE,
 * UNDELIVERABLE and FAILED.
 */
final class OtpSendStatus
{
    public const SENT = 'SENT';
    public const COOLDOWN = 'COOLDOWN';
    public const THROTTLED = 'THROTTLED';
    public const MASKED = 'MASKED';
    public const NO_ACCOUNT = 'NO_ACCOUNT';
    public const MULTIPLE = 'MULTIPLE';
    public const UNDELIVERABLE = 'UNDELIVERABLE';
    public const FAILED = 'FAILED';

    /** All that is answered while the reveal setting is off: neither depends on the number's accounts. */
    public const WITHOUT_REVEAL = [self::THROTTLED, self::MASKED];

    private function __construct()
    {
    }
}
