<?php
/**
 * A category with no products in it must not break its own page.
 *
 * WHAT HAPPENS WITHOUT THIS
 * -------------------------
 * A category listing sorted by position asks OpenSearch to sort on
 * `position_category_<id>`. That field only exists in the index for categories
 * that have at least one product in them — the indexer writes it per product —
 * so on an EMPTY category the field has no mapping and the whole query is
 * rejected:
 *
 *     query_shard_exception: No mapping found for [position_category_226]
 *     in order to sort on ... all shards failed
 *
 * Magento catches it and renders an empty listing, so the page looks right, but
 * every view fires a failing search and writes a CRITICAL to exception.log.
 * Measured on this store: three views, three criticals, for the two empty
 * categories a tester had just created ([CL036-TC13]).
 *
 * THE FIX
 * -------
 * `unmapped_type` tells the engine what to assume when a sort field is not in
 * the mapping at all — it then treats every document as missing that field and
 * sorts normally, instead of failing the shard. `long`, because this field is
 * an integer position wherever it does exist.
 *
 * It only ever applies where there is nothing to sort by anyway: the moment one
 * product is put in the category, the field is mapped and this changes nothing.
 *
 * NOT APPLIED TO `_script` SORTS. When a query spans several categories,
 * Position builds a painless script sort instead of a field sort, and
 * `unmapped_type` is not a valid option there — it would trade one 400 for
 * another.
 */
declare(strict_types=1);

namespace MagentoEgypt\SearchAttributeGuard\Plugin;

use Magento\Elasticsearch\SearchAdapter\Query\Builder\Sort\Position;

class PositionSortUnmapped
{
    /**
     * @param Position $subject
     * @param array $result
     * @return array
     */
    public function afterBuild(Position $subject, array $result): array
    {
        foreach ($result as $field => $params) {
            if ($field === '_script' || !is_array($params) || isset($params['unmapped_type'])) {
                continue;
            }
            $result[$field]['unmapped_type'] = 'long';
        }

        return $result;
    }
}
