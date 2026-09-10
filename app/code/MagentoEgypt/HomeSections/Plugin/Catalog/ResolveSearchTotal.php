<?php
/**
 * Make "N results" the total, not the size of the page you are on.
 *
 * WHAT WAS WRONG
 * --------------
 * Every paginated listing under-reported itself: /all.html said "12 results"
 * above five pages of them, page 5 of the same listing said "6 results", and a
 * search for "bag" said "10 results" above three pages. The number always came
 * out as the number of cards on the screen. The pager underneath it, reading the
 * same collection, had the right total — the two disagreed on the same page.
 *
 * WHY
 * ---
 * The listing collection is Mageplaza's fulltext collection. The true total
 * arrives with the search response, in _renderFiltersBefore(), which memoises it
 * in `_totalRecords`. Two things then work against the toolbar:
 *
 *   1. That method begins `if ($this->isLoaded()) { return; }`, and
 *      Magento\Catalog\Block\Product\ProductList\ListProduct::_beforeToHtml()
 *      loads the collection before any template runs.
 *   2. Loading clears `_totalRecords` — traced with the collection's object id:
 *      54 when the toolbar was handed the collection, null by the time the
 *      toolbar rendered.
 *
 * With the memo gone, AbstractDb::getSize() falls back to counting the CURRENT
 * select, and the search applier has already narrowed that to this page's ids.
 * Hence the page size, every time.
 *
 * THE FIX
 * -------
 * Read the total at the one moment it is certainly right — setCollection(), when
 * the page size and current page have just been applied and nothing has loaded
 * yet — and keep it on the block. getTotalNum() then answers with it.
 *
 * `max`, not "overwrite", in both directions: a page's own rows can never
 * outnumber the result set they came from, so the larger of the two is the
 * total, and a listing that has genuinely narrowed since (there is no such path
 * today) would still not be reported as smaller than what it is showing.
 *
 * `after`, not `before`, on setCollection: before it, the page size has not been
 * applied, and running the search then would page the result differently from
 * the grid.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Plugin\Catalog;

use Magento\Catalog\Block\Product\ProductList\Toolbar;

class ResolveSearchTotal
{
    /** Block data key holding the total read while the collection was fresh. */
    private const RESOLVED_TOTAL = 'hm_resolved_total';

    /**
     * @param Toolbar $subject
     * @param Toolbar $result
     * @return Toolbar
     */
    public function afterSetCollection(Toolbar $subject, Toolbar $result): Toolbar
    {
        $collection = $subject->getCollection();
        if ($collection === null) {
            return $result;
        }

        try {
            $size = (int) $collection->getSize();
        } catch (\Throwable $e) {
            /* A listing that renders with a wrong count beats one that 500s. */
            return $result;
        }

        if ($size > (int) $subject->getData(self::RESOLVED_TOTAL)) {
            $subject->setData(self::RESOLVED_TOTAL, $size);
        }

        return $result;
    }

    /**
     * @param Toolbar $subject
     * @param int|string $result
     * @return int
     */
    public function afterGetTotalNum(Toolbar $subject, $result): int
    {
        return max((int) $result, (int) $subject->getData(self::RESOLVED_TOTAL));
    }
}
