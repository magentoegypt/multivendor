<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

/**
 * Vnecoms RMA's stored values in the contract's words (HmReturnState, HmReturnType, HmReturnActor,
 * ISO-8601 UTC dates).
 */
final class Vocabulary
{
    /** ves_rma_request_entity.type */
    public const TYPE_REFUND = 'refund';
    public const TYPE_REPLACE = 'replace';

    /** ves_rma_request_entity.state; "awaiting" and "being" are VendorsRMA's escalation states. */
    private const OPEN_STATES = ['open', 'awaiting', 'being'];

    /** ves_rma_request_history.type */
    private const HISTORY_CUSTOMER = 'customer';
    private const HISTORY_SELLER = 'vendor';

    /** ves_rma_request_message.type */
    private const MESSAGE_CUSTOMER = 'CUSTOMER REPLY';
    private const MESSAGE_SELLER = 'VENDOR REPLY';

    private function __construct()
    {
    }

    /**
     * HmReturnState from the stored state, else from the status code (a status with no state row).
     */
    public static function state(string $state, string $statusCode): string
    {
        if ($state === 'canceled') {
            return 'CANCELED';
        }
        if ($state === 'closed') {
            return 'CLOSED';
        }
        if (in_array($state, self::OPEN_STATES, true)) {
            return 'OPEN';
        }
        if ($statusCode === 'canceled') {
            return 'CANCELED';
        }

        return $statusCode === 'resolved' ? 'CLOSED' : 'OPEN';
    }

    /**
     * True while the customer may still write to the return (Vnecoms\VendorsRMA\Block\Frontend\View::
     * isReplyRma: open, awaiting, being).
     */
    public static function acceptsReplies(string $state): bool
    {
        return in_array($state, self::OPEN_STATES, true);
    }

    public static function type(string $type): string
    {
        return $type === self::TYPE_REPLACE ? 'REPLACE' : 'REFUND';
    }

    public static function historyActor(string $changeType): string
    {
        if ($changeType === self::HISTORY_CUSTOMER) {
            return 'CUSTOMER';
        }

        return $changeType === self::HISTORY_SELLER ? 'SELLER' : 'HUB_MARKET';
    }

    public static function messageActor(string $messageType): string
    {
        if ($messageType === self::MESSAGE_CUSTOMER) {
            return 'CUSTOMER';
        }

        return $messageType === self::MESSAGE_SELLER ? 'SELLER' : 'HUB_MARKET';
    }

    /**
     * Money (value, currency) of an amount in the order currency; null when the currency is unknown.
     *
     * @return array{value: float, currency: string}|null
     */
    public static function money(float $value, string $currency): ?array
    {
        $currency = trim($currency);
        if ($currency === '') {
            return null;
        }

        return ['value' => round($value, 4), 'currency' => $currency];
    }

    /**
     * A UTC MySQL datetime/timestamp (Magento's connections run in UTC) as ISO-8601 with "Z";
     * '' when the value is empty or not a date.
     */
    public static function utc(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return '';
        }
        try {
            $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            return '';
        }

        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
