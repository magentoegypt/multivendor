<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Seller;

use MagentoEgypt\HomeSections\ViewModel\VendorNames;

/**
 * The storefront's seller-name rule as pure functions (design §9.2).
 *
 * One rule, the one the website's cards, cart, seller filter and Algolia facet
 * already use (HomeSections VendorNames::getName(), AlgoliaVendor SellerResolver):
 *
 *   - ves_vendor_entity.company when it is a name (VendorNames::isName(): a
 *     letter, or a digit other than 0; "", "0" and "." are not names);
 *   - otherwise the seller code made readable: "test_1" -> "Test 1", while
 *     casing the seller chose is kept ("MIA" stays "MIA");
 *   - vendor 0 (the store's own products) is "Hub Market", Latin in both
 *     locales like the storefront header.
 *
 * The result is the SOURCE text; SellerDirectory passes it through __() inside
 * storefront emulation, so the theme CSVs can localise it (vendor data has no
 * store scope, the company column feeds both store views).
 */
final class SellerName
{
    public const MARKETPLACE = 'Hub Market';

    private function __construct()
    {
    }

    /**
     * True when a seller-typed value can be shown as a name.
     *
     * Delegates to the storefront's own rule so the two can never drift; the
     * local copy only answers when HomeSections is not installed.
     */
    public static function isName(?string $name): bool
    {
        if (class_exists(VendorNames::class) && method_exists(VendorNames::class, 'isName')) {
            return VendorNames::isName($name);
        }

        return (bool) preg_match('/[\p{L}1-9]/u', (string) $name);
    }

    /**
     * The seller code as a readable name: separators become spaces and
     * all-lowercase words are capitalised; existing capitals are kept.
     */
    public static function humanise(string $code): string
    {
        $words = preg_split('/\s+/u', trim(str_replace(['_', '-', '.'], ' ', $code))) ?: [];
        $words = array_filter($words, static fn (string $word): bool => $word !== '');

        return implode(' ', array_map(
            static fn (string $word): string => $word === mb_strtolower($word, 'UTF-8')
                ? mb_convert_case($word, MB_CASE_TITLE, 'UTF-8')
                : $word,
            $words
        ));
    }

    /**
     * Untranslated display name of a seller: company when it is a name, else the humanised code.
     */
    public static function source(?string $company, ?string $code): string
    {
        $company = trim((string) $company);

        return self::isName($company) ? $company : self::humanise(trim((string) $code));
    }
}
