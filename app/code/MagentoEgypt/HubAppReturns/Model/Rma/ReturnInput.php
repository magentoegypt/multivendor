<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

/**
 * Rules for the free text a return stores that the admin and seller panels print unescaped.
 *
 * Vnecoms' request/edit/tableft.phtml (Vnecoms_RMA adminhtml, Vnecoms_VendorsRMA adminhtml and vendors)
 * echoes the reason (Request::getReasonTitle(), which is other_reason when no listed reason is set),
 * the tracking code (as text and inside value="...") and the client address as they are stored, and
 * request/message/list.phtml echoes each message. Whatever the app stores there must not be able to
 * carry markup:
 *
 *  - other_reason: free text, so only the characters that open markup or leave a quoted attribute are
 *    refused (< > "); an apostrophe, an ampersand or Arabic text stays as typed;
 *  - tracking_code: a carrier reference, so an allow-list (letters, digits, space . _ / # -);
 *  - ip_address: Vnecoms stores X-Forwarded-For as sent (Helper\Config::getClientIP()); only valid
 *    addresses are kept;
 *  - messages are stored HTML-escaped by MessageBody::fromPlainText().
 */
final class ReturnInput
{
    /** Characters refused in other_reason. */
    public const REASON_FORBIDDEN = '<>"';

    private function __construct()
    {
    }

    public static function isSafeReason(string $reason): bool
    {
        return strpbrk($reason, self::REASON_FORBIDDEN) === false;
    }

    /**
     * Letters, digits, spaces and . _ / # - only (empty is not a tracking code).
     */
    public static function isTrackingCode(string $code): bool
    {
        return preg_match('~^[A-Za-z0-9 ._/#-]+$~D', $code) === 1;
    }

    /**
     * The client address stored on a return: the valid addresses of what the website would store
     * ($reported: X-Forwarded-For first, as Vnecoms reads it, possibly a comma-separated chain), else
     * the connection's own address, else nothing.
     */
    public static function clientIp(string $reported, string $remote): string
    {
        $valid = [];
        foreach (explode(',', $reported) as $part) {
            $part = trim($part);
            if ($part !== '' && filter_var($part, FILTER_VALIDATE_IP) !== false) {
                $valid[] = $part;
            }
        }
        if ($valid) {
            return implode(', ', array_values(array_unique($valid)));
        }
        $remote = trim($remote);

        return filter_var($remote, FILTER_VALIDATE_IP) !== false ? $remote : '';
    }
}
