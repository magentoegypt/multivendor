<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Plugin;

use Algolia\SearchAdapter\Service\QueryParamBuilder;

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
 */
class ServerSearchNoAnalytics
{
    public function afterBuild(QueryParamBuilder $subject, array $result): array
    {
        $result['analytics'] = false;

        return $result;
    }
}
