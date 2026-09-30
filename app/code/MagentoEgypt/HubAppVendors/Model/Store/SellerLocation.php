<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

/**
 * The store page's location line, as the website builds it. Pure: no Magento.
 *
 *   1. Vnecoms Block\Profile::getAddress(): the admin's address template
 *      (vendors/profile/address_template, "{{var region}}, {{var country}}" by
 *      default) filled with street, city, region, postcode and the country's
 *      name in the store view's language; trimmed of spaces, then of commas.
 *   2. The theme's profile/address.phtml: the city and the region are free text
 *      the seller typed once for both store views, so each is swapped for its
 *      translation (theme CSV) inside the composed line, longest first; then
 *      the seams a trailing space left behind (" ," and double spaces) are
 *      tidied.
 */
final class SellerLocation
{
    private function __construct()
    {
    }

    /**
     * The template with its {{var name}} directives filled, trimmed as Vnecoms does.
     *
     * The template is filtered by the CMS template filter on the website; its
     * var directive prints the variable as is. Any other directive prints
     * nothing here.
     *
     * @param array<string, string> $variables street, city, country, region, postcode
     */
    public static function compose(string $template, array $variables): string
    {
        $address = (string) preg_replace_callback(
            '/\{\{\s*var\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/',
            static fn (array $match): string => (string) ($variables[$match[1]] ?? ''),
            $template
        );
        $address = (string) preg_replace('/\{\{.*?\}\}/s', '', $address);

        //  Vnecoms trims spaces, then commas; the theme trims the result again, so an
        //  empty region leaves "United Arab Emirates", not " United Arab Emirates".
        return trim(trim(trim($address), ','));
    }

    /**
     * The composed line with the city and region translated and the seams tidied (address.phtml).
     *
     * @param string[] $tokens the seller's city and region, as stored
     * @param callable(string): string $translate __() of the storefront
     */
    public static function localise(string $address, array $tokens, callable $translate): string
    {
        $tokens = array_values(array_unique(array_filter(
            array_map(static fn ($token): string => trim((string) $token), $tokens),
            static fn (string $token): bool => $token !== ''
        )));
        usort($tokens, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($tokens as $token) {
            $localised = (string) $translate($token);
            if ($localised !== $token) {
                $address = str_replace($token, $localised, $address);
            }
        }

        $address = (string) preg_replace('/\s+,/u', ',', $address);

        return trim((string) preg_replace('/\s{2,}/u', ' ', $address));
    }
}
