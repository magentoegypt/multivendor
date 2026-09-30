<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Model\Otp;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MagentoEgypt\SmsExtend\Model\Throttle;

/**
 * Abuse limits of the WhatsApp codes, one policy for every channel that sends or checks them: the REST
 * service /V1/whatsapp/otp/* (the seller app, WhatsAppManagement) and the app's GraphQL
 * (MagentoEgypt_HubAppAccount's WhatsAppSignIn). Both count on the same counters.
 *
 * Sending (every code is a paid message): an hourly limit per number, and optionally per client address,
 * counted for every request, so a limit answers alike for numbers with and without an account.
 *
 * Checking: five wrong codes for a number within 15 minutes of each other lock code checks for that
 * number for 15 minutes (whatever the account, and whether or not it has one), and each address can have
 * an hourly budget of wrong codes. This lock is the codes' own: it never touches the customer's account
 * lock, so nobody can lock a customer out of password sign-in by sending wrong codes. A number with no
 * account, or with several, counts like a wrong code.
 *
 * The limits are the Hub Market App settings (Stores > Configuration > Magento Egypt > Hub Market App >
 * WhatsApp Codes), which MagentoEgypt_HubAppAccount declares; they are read here by path, so this module
 * needs no HubApp module, and when nothing is stored the defaults below apply. 0 switches a limit off.
 *
 * The two per-address limits are 0 (the default) = off. Turn them on only after checking that the server
 * sees each visitor's own address, not the CDN's; otherwise all visitors share one limit. The address is
 * Magento's RemoteAddress (REMOTE_ADDR unless alternative headers are configured): behind a CDN that is
 * the edge's address, so one busy hour would stop the codes of every seller and customer at once.
 * Suggested values once on: 10 code requests and 30 wrong codes per address per hour. The per-number
 * limits do not depend on the address and are on by default.
 *
 * Numbers are counted by their OTP key (MobileNumber::key: every spelling of a number is that number);
 * numbers and addresses are hashed, never stored in a cache key.
 */
class OtpGuard
{
    public const XML_SEND_LIMIT_IP = 'hubapp/otp/send_limit_ip_hour';
    public const XML_SEND_LIMIT_NUMBER = 'hubapp/otp/send_limit_number_hour';
    public const XML_WRONG_CODES_IP = 'hubapp/otp/wrong_codes_ip_hour';

    /** Off until the server is known to see each visitor's own address (suggested once on: 10). */
    public const DEFAULT_SEND_LIMIT_IP = 0;
    public const DEFAULT_SEND_LIMIT_NUMBER = 5;
    /** Off until the server is known to see each visitor's own address (suggested once on: 30). */
    public const DEFAULT_WRONG_CODES_IP = 0;

    /** Wrong codes for one number that lock code checks for it. */
    public const NUMBER_FAILURES = 5;

    /** How long the lock lasts, and how long a wrong code is remembered. */
    public const NUMBER_LOCK_SECONDS = 900;

    private const HOUR = 3600;
    private const PREFIX = 'me_otp_number_';

    public function __construct(
        private readonly Throttle $throttle,
        private readonly CacheInterface $cache,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Counts one code request for the address and for the number.
     *
     * @param int|null $now unix time (tests)
     * @return int 0 when the code may be sent, else the seconds to wait
     */
    public function sendWait(string $mobile, string $clientIp, ?int $now = null): int
    {
        $wait = $this->throttle->consume(
            'otp_ip',
            $clientIp,
            $this->limit(self::XML_SEND_LIMIT_IP, self::DEFAULT_SEND_LIMIT_IP),
            self::HOUR,
            $now
        );
        if ($wait === 0) {
            $wait = $this->throttle->consume(
                'otp_number',
                MobileNumber::key($mobile),
                $this->limit(self::XML_SEND_LIMIT_NUMBER, self::DEFAULT_SEND_LIMIT_NUMBER),
                self::HOUR,
                $now
            );
        }

        return $wait;
    }

    /**
     * Whether a code may be checked now for this number from this address; counts nothing.
     *
     * @return int 0 when it may, else the seconds to wait (the number's lock, or the address's budget)
     */
    public function verifyWait(string $mobile, string $clientIp, ?int $now = null): int
    {
        $now ??= time();
        $addressWait = $this->throttle->peek(
            'otp_wrong_ip',
            $clientIp,
            $this->limit(self::XML_WRONG_CODES_IP, self::DEFAULT_WRONG_CODES_IP),
            self::HOUR,
            $now
        );
        $state = $this->state($mobile);

        return max($addressWait, $state['until'] > $now ? $state['until'] - $now : 0);
    }

    /**
     * Counts a failed check: a wrong or expired code, or a number with no account or several.
     *
     * @return int the lock this failure started (NUMBER_LOCK_SECONDS), else 0
     */
    public function verifyFailed(string $mobile, string $clientIp, ?int $now = null): int
    {
        $now ??= time();
        if ($this->limit(self::XML_WRONG_CODES_IP, self::DEFAULT_WRONG_CODES_IP) > 0) {
            //  Counted only while the budget is on, as the send limits are (Throttle::consume).
            $this->throttle->hit('otp_wrong_ip', $clientIp, self::HOUR, $now);
        }

        $state = $this->state($mobile);
        $failures = $now - $state['at'] > self::NUMBER_LOCK_SECONDS ? 1 : $state['n'] + 1;
        $until = $state['until'];
        $started = 0;
        if ($failures >= self::NUMBER_FAILURES) {
            $until = $now + self::NUMBER_LOCK_SECONDS;
            $failures = 0;
            $started = self::NUMBER_LOCK_SECONDS;
        }
        $this->cache->save(
            (string) json_encode(['n' => $failures, 'at' => $now, 'until' => $until]),
            $this->key($mobile),
            [Throttle::CACHE_TAG],
            self::NUMBER_LOCK_SECONDS
        );

        return $started;
    }

    /**
     * A right code: the number's wrong codes are forgotten.
     */
    public function verifySucceeded(string $mobile): void
    {
        $this->cache->remove($this->key($mobile));
    }

    /**
     * @return array{n: int, at: int, until: int} wrong codes, when the last one came, locked until
     */
    private function state(string $mobile): array
    {
        $raw = $this->cache->load($this->key($mobile));
        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            return ['n' => 0, 'at' => 0, 'until' => 0];
        }

        return [
            'n' => (int) ($data['n'] ?? 0),
            'at' => (int) ($data['at'] ?? 0),
            'until' => (int) ($data['until'] ?? 0),
        ];
    }

    private function key(string $mobile): string
    {
        return self::PREFIX . sha1(MobileNumber::key($mobile));
    }

    private function limit(string $path, int $default): int
    {
        $value = $this->scopeConfig->getValue($path);
        if ($value === null || $value === '') {
            return $default;
        }

        return max(0, (int) $value);
    }
}
