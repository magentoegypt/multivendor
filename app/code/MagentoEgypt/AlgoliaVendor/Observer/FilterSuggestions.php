<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Search\Model\ResourceModel\Query\Collection as QueryCollection;

/**
 * Keeps hidden search terms out of the Algolia suggestions index.
 *
 * WHY THIS IS NEEDED
 * ------------------
 * Magento already has a per-term switch for exactly this: `display_in_terms` on
 * `search_query`, surfaced in the admin as Marketing > SEO & Search > Search
 * Terms > "Display in Suggested Terms". Algolia does not read it —
 * SuggestionHelper::getSuggestionCollectionQuery() filters on `num_results`,
 * `popularity` and `query_text != "__empty__"` and nothing else — so a term the
 * merchant has explicitly hidden is still published to shoppers through
 * autocomplete.
 *
 * On this install that is not theoretical. The search log is a development log:
 * "test" (126 uses), "a" (82), "text", "test61", "7888-1" all rank ABOVE real
 * terms like "shirt" and "حقيبة", so there is no popularity threshold that keeps
 * the noise out and the real queries in. The threshold and this filter are two
 * different jobs: `min_popularity` answers "is this term used enough?", this
 * answers "does the merchant want it shown at all?".
 *
 * WHY THE EVENT AND NOT A PLUGIN
 * ------------------------------
 * `algolia_after_suggestions_collection_build` is Algolia's own documented hook
 * and is dispatched with the collection still unloaded, which is the last point
 * the SELECT can still be narrowed. Consistent with the rest of this module: no
 * plugin on, and no preference for, an Algolia class.
 *
 * SCOPE. Only the INDEXING path goes through this event. SuggestionHelper's
 * other query, getPopularQueries() (the no-results-page block), dispatches
 * nothing and cannot be hooked — it is inert here anyway, being gated on
 * `isInstantEnabled()`, which is 0 on this install. If InstantSearch is ever
 * turned on, that path will need its own fix.
 *
 * FAIL OPEN. Anything unexpected in the payload leaves the collection alone: a
 * suggestions index that is too broad is a cosmetic problem, one that throws
 * during indexing takes the whole queue job down with it.
 */
class FilterSuggestions implements ObserverInterface
{
    /**
     * `1` is the column's default, so terms nobody has touched are unaffected —
     * only a deliberate "no" in the admin (or a deliberate UPDATE) hides a term.
     */
    private const SHOWN = 1;

    public function execute(Observer $observer): void
    {
        $collection = $observer->getData('collection');

        if (!$collection instanceof QueryCollection) {
            return;
        }

        //  addFieldToFilter() rather than getSelect()->where(): it qualifies the
        //  column with the collection's own alias, so this keeps working if
        //  Algolia ever joins another table into that select.
        $collection->addFieldToFilter('display_in_terms', self::SHOWN);
    }
}
