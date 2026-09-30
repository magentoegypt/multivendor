<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Link;

use MagentoEgypt\HubApp\Api\LinkResolverInterface as Link;

/**
 * Pure classification of one configured destination, for one store view.
 *
 * No database and no Magento objects: everything store-specific comes in
 * through the constructor, so the rules are unit-testable. LinkResolver builds
 * one per store and resolves the LOOKUP results against url_rewrite.
 *
 * Order (first match wins):
 *   1. empty                                   -> null
 *   2. absolute URL on another host / mailto:  -> EXTERNAL
 *      absolute URL on this store              -> its path, classified below
 *   3. "<seller route>/<code>"  (shop/loly)    -> STORE, code = seller code
 *      "<seller route>" or "sellerlist"        -> STORES
 *   4. "<brand route>/<url_key>"               -> BRAND, code = url_key
 *      "<brand route>", "brand"                -> BRANDS
 *   5. "bundles"                               -> BUNDLES
 *   6. "deals"                                 -> DEALS (app-only; the website has no such page)
 *   7. "catalogsearch/result?q=…"              -> SEARCH, code = the query text
 *   8. anything else                           -> LOOKUP (url_rewrite decides)
 *
 * A leading store-code segment ("en/clothes.html") is dropped, because the
 * storefront URLs carry the store code and merchandisers paste them.
 */
final class LinkClassifier
{
    /** Needs a url_rewrite lookup (category, product, CMS page) — not an HmLinkType. */
    public const LOOKUP = 'LOOKUP';

    /** @var string[] longest first */
    private array $baseUrls;

    /**
     * @param string[] $baseUrls absolute store base URLs (link and web, secure and unsecure)
     * @param string $storeCode store view code, e.g. en
     * @param string $sellerRoute vendors/vendorspage/url_key, e.g. shop ('' when unset)
     * @param string $brandRoute brand/general_settings/route, e.g. shop-by-brand ('' when unset)
     */
    public function __construct(
        array $baseUrls,
        private readonly string $storeCode,
        private readonly string $sellerRoute,
        private readonly string $brandRoute
    ) {
        $bases = [];
        foreach ($baseUrls as $url) {
            $url = trim((string) $url);
            if ($url !== '') {
                $bases[] = rtrim($url, '/') . '/';
            }
        }
        $bases = array_values(array_unique($bases));
        usort($bases, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        $this->baseUrls = $bases;
    }

    /**
     * @return array{type: string, path: string|null, query: string, code: string|null, external: string|null}|null
     *         `path` is store-relative without leading slash and without the query;
     *         `external` is the untouched absolute URL for EXTERNAL results.
     */
    public function classify(?string $target): ?array
    {
        $target = trim((string) $target);
        if ($target === '') {
            return null;
        }

        if (preg_match('~^(?:https?:)?//~i', $target)) {
            $relative = $this->stripBase($target);
            if ($relative === null) {
                return $this->result(Link::TYPE_EXTERNAL, null, '', null, $target);
            }
            $target = $relative;
        } elseif (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $target)) {
            //  mailto:, tel:, whatsapp: … never a storefront page
            return $this->result(Link::TYPE_EXTERNAL, null, '', null, $target);
        }

        $query = '';
        $hash = strpos($target, '#');
        if ($hash !== false) {
            $target = substr($target, 0, $hash);
        }
        $mark = strpos($target, '?');
        if ($mark !== false) {
            $query = substr($target, $mark + 1);
            $target = substr($target, 0, $mark);
        }

        $path = trim($target, '/');
        $path = $this->stripStoreCode($path);
        if ($path === '') {
            //  The store's home page: nothing to route to natively.
            return $this->result(Link::TYPE_EXTERNAL, '', $query, null, null);
        }

        $segments = explode('/', $path);
        $first = strtolower($segments[0]);
        $second = isset($segments[1]) ? trim($segments[1]) : '';
        $lower = strtolower($path);

        if ($this->sellerRoute !== '' && $first === strtolower($this->sellerRoute)) {
            return $second !== ''
                ? $this->result(Link::TYPE_STORE, $path, $query, $second, null)
                : $this->result(Link::TYPE_STORES, $path, $query, null, null);
        }
        if ($first === 'sellerlist') {
            return $this->result(Link::TYPE_STORES, $path, $query, null, null);
        }

        if ($this->brandRoute !== '' && $first === strtolower($this->brandRoute)) {
            return $second !== ''
                ? $this->result(Link::TYPE_BRAND, $path, $query, (string) preg_replace('~\.html?$~i', '', $second), null)
                : $this->result(Link::TYPE_BRANDS, $path, $query, null, null);
        }
        if ($lower === 'brand' || $lower === 'brand/index' || $lower === 'brand/index/index') {
            return $this->result(Link::TYPE_BRANDS, $path, $query, null, null);
        }

        if ($lower === 'bundles' || $lower === 'bundles.html') {
            return $this->result(Link::TYPE_BUNDLES, $path, $query, null, null);
        }
        if ($lower === 'deals' || $lower === 'deals.html') {
            return $this->result(Link::TYPE_DEALS, $path, $query, null, null);
        }

        if ($lower === 'catalogsearch/result' || $lower === 'catalogsearch/result/index') {
            parse_str($query, $params);
            $text = trim((string) ($params['q'] ?? ''));

            return $text !== ''
                ? $this->result(Link::TYPE_SEARCH, $path, $query, $text, null)
                : $this->result(Link::TYPE_EXTERNAL, $path, $query, null, null);
        }

        return $this->result(self::LOOKUP, $path, $query, null, null);
    }

    /**
     * Store-relative remainder of an absolute URL on this store, or null for another host.
     */
    private function stripBase(string $url): ?string
    {
        $candidate = str_starts_with($url, '//') ? 'https:' . $url : $url;
        foreach ($this->baseUrls as $base) {
            if (stripos($candidate, $base) === 0) {
                return substr($candidate, strlen($base));
            }
            //  The same base typed without its trailing slash (the home page).
            if (strcasecmp(rtrim($candidate, '/'), rtrim($base, '/')) === 0) {
                return '';
            }
        }

        return null;
    }

    private function stripStoreCode(string $path): string
    {
        if ($this->storeCode === '') {
            return $path;
        }
        if (strcasecmp($path, $this->storeCode) === 0) {
            return '';
        }
        if (stripos($path, $this->storeCode . '/') === 0) {
            return ltrim(substr($path, strlen($this->storeCode) + 1), '/');
        }

        return $path;
    }

    /**
     * @return array{type: string, path: string|null, query: string, code: string|null, external: string|null}
     */
    private function result(string $type, ?string $path, string $query, ?string $code, ?string $external): array
    {
        return [
            'type' => $type,
            'path' => $path,
            'query' => $query,
            'code' => $code,
            'external' => $external,
        ];
    }
}
