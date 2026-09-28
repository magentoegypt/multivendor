<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Plugin;

use Algolia\SearchAdapter\Service\QueryParamBuilder;
use MagentoEgypt\AlgoliaVendor\Model\IdRestriction;

/**
 * Server-side (SearchAdapter) searches are not counted in Algolia Analytics.
 *
 * Category and search results pages are rendered by Magento and cached, so the
 * server's query runs once per cache miss, from the server's IP, with no
 * shopper token and no queryID the shopper can use — Analytics would show
 * cache misses and "<empty search>" category browses, not what shoppers typed.
 * The search results page instead records the search from the browser
 * (Algolia_AlgoliaSearch/js/hm-search-results-insights.js, clickAnalytics on), which
 * also gives the queryID that click and conversion events need (DEV06).
 *
 * It also applies Model\IdRestriction: the product ids the non-attribute
 * layered filters (Vendors, Minimum Rating, Availability) allow, so Algolia's
 * total and paging follow those filters (QA01 2026-09-25).
 *
 * And it personalizes the search RESULTS page for the shopper — see
 * personalizeSearchResults().
 */
class ServerSearchNoAnalytics
{
    /** The only route rendered per shopper: Varnish passes it for them (default.vcl). */
    private const PERSONALIZED_ACTION = 'catalogsearch_result_index';

    public function afterBuild(QueryParamBuilder $subject, array $result, $request = null): array
    {
        $result['analytics'] = false;
        $result = $this->personalizeSearchResults($result);

        $restriction = IdRestriction::filterExpression();

        if ($restriction !== null) {
            $existing = trim((string) ($result['filters'] ?? ''));
            $result['filters'] = $existing === '' ? $restriction : '(' . $existing . ') AND ' . $restriction;
        }

        return $this->fixMultiCategoryFilter($result, $request);
    }

    /**
     * Search with the shopper's own Algolia token, so Personalization ranks the
     * WHOLE result set for them (DEV05, QA01 retest 2026-09-27).
     *
     * Until now the server searched without a token and hm-personalized-order.js
     * re-ordered only the products already on page 1: a personal match ranked
     * 13th never reached the page ("women": 3 of the Fashion shopper's 4 lifted
     * items). Only for the search results route, with cookie consent and the
     * anonymous `_ALGOLIA` token — the token Algolia builds profiles on. For
     * exactly those visitors Varnish passes the route instead of serving the
     * shared cached copy (vcl_recv, "hm personalized search"); everyone else,
     * and every category page, still gets the tokenless, cached result. The
     * page-1 script is not loaded on this route any more, so nothing is lifted
     * twice.
     */
    private function personalizeSearchResults(array $result): array
    {
        try {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $request = $om->get(\Magento\Framework\App\RequestInterface::class);

            if (!$request instanceof \Magento\Framework\App\Request\Http
                || $request->getFullActionName() !== self::PERSONALIZED_ACTION
            ) {
                return $result;
            }

            $insights = $om->get(\Algolia\AlgoliaSearch\Helper\InsightsHelper::class);
            $token = (string) $insights->getAnonymousUserToken();

            // Same shape Algolia accepts for a userToken; anything else is not ours.
            if (!$insights->getUserAllowedSavedCookie() || !preg_match('/^[A-Za-z0-9_=+\/.-]{1,129}$/', $token)) {
                return $result;
            }

            $result['userToken'] = $token;
            $result['enablePersonalization'] = true;
        } catch (\Throwable $e) {
            // Personalization is an enhancement; the unpersonalized search stands.
        }

        return $result;
    }

    /**
     * Mageplaza's category filter hands the search request an ARRAY of category
     * ids (it supports picking several), and the adapter's CategoryFilterHandler
     * formats the value with sprintf('%u') — every array becomes 1. So each
     * "See products in <category>" link (`?q=…&cat=222`) searched category 1,
     * the root no product lists, and landed on an empty page with no filters or
     * sorting (QA01 2026-09-25, BUG-07); the merchandising rule context became
     * "magento-category-Array". Rebuild both from the request's real ids:
     * several categories are OR'd, as Mageplaza intends.
     */
    private function fixMultiCategoryFilter(array $result, $request): array
    {
        if (!in_array('magento-category-Array', (array) ($result['ruleContexts'] ?? []), true)
            || !$request instanceof \Magento\Framework\Search\RequestInterface
        ) {
            return $result;
        }

        $ids = [];

        try {
            $query = $request->getQuery();
            $filter = $query instanceof \Magento\Framework\Search\Request\Query\BoolExpression
                ? ($query->getMust()['category'] ?? null)
                : null;
            $value = (array) ($filter && method_exists($filter, 'getReference') ? $filter->getReference()->getValue() : []);
            array_walk_recursive($value, static function ($id) use (&$ids): void {
                if (is_numeric($id) && (int) $id > 0) {
                    $ids[] = (int) $id;
                }
            });
        } catch (\Throwable $e) {
            return $result;
        }

        $ids = array_values(array_unique($ids));

        if (!$ids) {
            return $result;
        }

        foreach ((array) ($result['facetFilters'] ?? []) as $i => $facetFilter) {
            if ($facetFilter === 'categoryIds:1') {
                $or = array_map(static fn(int $id): string => 'categoryIds:' . $id, $ids);
                $result['facetFilters'][$i] = count($or) === 1 ? $or[0] : $or;
            }
        }

        $result['ruleContexts'] = array_values(array_filter(
            (array) $result['ruleContexts'],
            static fn($context) => $context !== 'magento-category-Array'
        ));

        if (count($ids) === 1) {
            $result['ruleContexts'][] = 'magento-category-' . $ids[0];
        }

        return $result;
    }
}
