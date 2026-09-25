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
 * (Algolia_AlgoliaSearch/js/hm-results-insights.js, clickAnalytics on), which
 * also gives the queryID that click and conversion events need (DEV06).
 *
 * It also applies Model\IdRestriction: the product ids the non-attribute
 * layered filters (Vendors, Minimum Rating, Availability) allow, so Algolia's
 * total and paging follow those filters (QA01 2026-09-25).
 */
class ServerSearchNoAnalytics
{
    public function afterBuild(QueryParamBuilder $subject, array $result, $request = null): array
    {
        $result['analytics'] = false;

        $restriction = IdRestriction::filterExpression();

        if ($restriction !== null) {
            $existing = trim((string) ($result['filters'] ?? ''));
            $result['filters'] = $existing === '' ? $restriction : '(' . $existing . ') AND ' . $restriction;
        }

        return $this->fixMultiCategoryFilter($result, $request);
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
