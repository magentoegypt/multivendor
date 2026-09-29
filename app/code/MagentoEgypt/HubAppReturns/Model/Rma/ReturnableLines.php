<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

/**
 * Which lines of an order a customer picks when filing a return: the lines the website's return form
 * offers, which are also the lines the admin and seller panels list on a return.
 *
 * Vnecoms draws the form's item list (new_request/create/items.phtml) and the panels' item list of a
 * return (request/edit/items.phtml) from the order's top-level lines, each through the item renderer
 * of its product type. The RMA layouts (*_item_renderers.xml) declare two renderers, and no module or
 * theme adds another:
 *
 *  - "bundle" (new_request/create/bundle.phtml): the bundle's own line is a header without a checkbox;
 *    each child line (one per chosen selection) has its checkbox and its own returnable quantity. On a
 *    return, the panels' request/edit/bundle.phtml lists the bundle's child lines the return holds and
 *    never the bundle line itself, so a return filed on a bundle line shows no items there;
 *  - "default" for every other type: the top-level line is the returnable one and its child lines are
 *    never shown (a configurable's simple line, and BundleExtend's "new_bundle": no RMA renderer maps
 *    it, so the website offers, and the panels list, the new_bundle line itself, not its selections).
 *
 * So the lines offered are the child lines of a "bundle" line, in its place, and every other
 * top-level line; nothing else.
 */
final class ReturnableLines
{
    /** Product types returned through their child lines: the types the RMA layouts render as "bundle". */
    public const RETURNED_BY_CHILD_LINES = ['bundle'];

    /** A line the customer can pick. */
    public const OFFERED = 'offered';

    /** Not a line of these orders. */
    public const UNKNOWN = 'unknown';

    /** A bundle line: its child lines are picked instead. */
    public const BUNDLE = 'bundle';

    /** A child line the website never offers on its own (its top-level line is the returnable one). */
    public const PART = 'part';

    private function __construct()
    {
    }

    /**
     * The lines a customer can pick among these, in the order given.
     *
     * @param array<int, array<string, mixed>> $lines item id => sales_order_item row (item_id,
     *     parent_item_id, product_type, ...): every line of the orders concerned, children included
     * @return array<int, array<string, mixed>>
     */
    public static function offered(array $lines): array
    {
        $out = [];
        foreach ($lines as $itemId => $line) {
            if (self::classify($lines, (int) $itemId) === self::OFFERED) {
                $out[(int) $itemId] = $line;
            }
        }

        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $lines every line of the order, children included
     * @return string OFFERED, UNKNOWN, BUNDLE or PART
     */
    public static function classify(array $lines, int $itemId): string
    {
        $line = $lines[$itemId] ?? null;
        if (!is_array($line)) {
            return self::UNKNOWN;
        }
        if (self::isTopLevel($line)) {
            return self::isReturnedByChildLines($line) ? self::BUNDLE : self::OFFERED;
        }

        return self::bundleOf($lines, $itemId) !== null ? self::OFFERED : self::PART;
    }

    /**
     * The top-level bundle line a child line belongs to, when that bundle is returned through its child
     * lines; null for any other line.
     *
     * @param array<int, array<string, mixed>> $lines
     * @return array<string, mixed>|null
     */
    public static function bundleOf(array $lines, int $itemId): ?array
    {
        $line = $lines[$itemId] ?? null;
        if (!is_array($line) || self::isTopLevel($line)) {
            return null;
        }
        $parent = $lines[(int) $line['parent_item_id']] ?? null;
        if (!is_array($parent) || !self::isTopLevel($parent) || !self::isReturnedByChildLines($parent)) {
            return null;
        }

        return $parent;
    }

    /**
     * @param array<string, mixed> $line
     */
    private static function isTopLevel(array $line): bool
    {
        return (int) ($line['parent_item_id'] ?? 0) <= 0;
    }

    /**
     * @param array<string, mixed> $line
     */
    private static function isReturnedByChildLines(array $line): bool
    {
        return in_array((string) ($line['product_type'] ?? ''), self::RETURNED_BY_CHILD_LINES, true);
    }
}
