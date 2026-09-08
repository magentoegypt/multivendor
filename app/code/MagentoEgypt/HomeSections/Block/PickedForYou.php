<?php
/**
 * Hub Market — "Picked For You".
 *
 * The Figma reference badges this panel "AI ENGINE". There is no recommendation
 * model on this install and this block does not pretend otherwise — it uses two
 * real signals, in order of how personal they are:
 *
 *   1. THE VISITOR'S OWN recently-viewed products (report_viewed_product_index).
 *      Genuine personalisation, populated by Magento as people browse.
 *   2. Failing that, the highest-RATED products — 136 products carry approved
 *      customer reviews here, so "things other shoppers rated well" is a real
 *      answer rather than a random grid.
 *
 * Signal 1 is empty on a fresh install (no browsing traffic yet), which is why
 * the earlier attempt using Magento's stock recently-viewed widget rendered
 * nothing at all. The fallback means the section is useful from day one and
 * quietly becomes personal as soon as a visitor has history.
 *
 * Extends BestSellers for the _beforeToHtml hand-off — see that class for why
 * overriding createCollection() does not work here.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Block;

class PickedForYou extends BestSellers
{
    /** @var bool true when the ids came from this visitor's own history */
    private bool $personalised = false;

    /** @var int[] the subset of ids that came from history, keyed by product id */
    private array $hmViewedIds = [];

    /** @var array<int,string>|null lazily-built product id -> category name */
    private ?array $hmCategoryOf = null;

    /**
     * Injected explicitly. AbstractProduct does NOT expose a customer session and
     * Catalog\Block\Product\Context has no getter for one — assuming
     * $this->_customerSession existed would have fataled at render time, which is
     * the same class of mistake that took the homepage down with `static fn`.
     */
    private \Magento\Customer\Model\Session $hmCustomerSession;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Rule\Model\Condition\Sql\Builder $sqlBuilder,
        \Magento\CatalogWidget\Model\Rule $rule,
        \Magento\Widget\Helper\Conditions $conditionsHelper,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Customer\Model\Session $customerSession,
        array $data = [],
        ?\Magento\Framework\Serialize\Serializer\Json $json = null,
        ?\Magento\Framework\View\LayoutFactory $layoutFactory = null,
        ?\Magento\Framework\Url\EncoderInterface $urlEncoder = null,
        ?\Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository = null
    ) {
        $this->hmCustomerSession = $customerSession;
        parent::__construct(
            $context, $productCollectionFactory, $catalogProductVisibility, $httpContext,
            $sqlBuilder, $rule, $conditionsHelper, $resource, $data, $json,
            $layoutFactory, $urlEncoder, $categoryRepository
        );
    }

    /**
     * @return int[]
     */
    protected function getRankedProductIds(): array
    {
        $limit = (int) ($this->getData('products_count') ?: 4);

        $ids = $this->viewedByThisVisitor($limit);
        if ($ids) {
            $this->personalised = true;
            $this->hmViewedIds = array_flip($ids);
            return $ids;
        }

        return $this->topRated($limit);
    }

    /**
     * This visitor's own recently-viewed products, newest first.
     *
     * @return int[]
     */
    private function viewedByThisVisitor(int $limit): array
    {
        $conn  = $this->hmResource->getConnection();
        $table = $this->hmResource->getTableName('report_viewed_product_index');

        try {
            $visitorId  = $this->hmCustomerSession->getVisitorData()['visitor_id'] ?? null;
            $customerId = $this->hmCustomerSession->getCustomerId();
        } catch (\Throwable $e) {
            return [];
        }

        if (!$visitorId && !$customerId) {
            return [];
        }

        try {
            $select = $conn->select()
                ->from(['v' => $table], ['product_id'])
                ->order('v.added_at DESC')
                ->limit($limit * 3);

            // Prefer the logged-in customer's history; fall back to the guest visitor.
            if ($customerId) {
                $select->where('v.customer_id = ?', (int) $customerId);
            } else {
                $select->where('v.visitor_id = ?', (int) $visitorId);
            }

            return array_map('intval', array_column($conn->fetchAll($select), 'product_id'));
        } catch (\Throwable $e) {
            $this->_logger->warning('Hub Market picked-for-you (viewed): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Best-reviewed products as the impersonal fallback.
     *
     * rating_summary is a PERCENTAGE, and a single 5-star review would otherwise
     * outrank a product with fifty good ones — so require at least two reviews
     * and order by rating then volume.
     *
     * @return int[]
     */
    private function topRated(int $limit): array
    {
        $conn    = $this->hmResource->getConnection();
        $summary = $this->hmResource->getTableName('review_entity_summary');
        $review  = $this->hmResource->getTableName('review');

        try {
            $select = $conn->select()
                ->from(['s' => $summary], ['product_id' => 's.entity_pk_value'])
                ->join(['r' => $review], 'r.entity_pk_value = s.entity_pk_value', [])
                ->where('r.status_id = ?', 1)
                ->where('s.rating_summary IS NOT NULL')
                ->group('s.entity_pk_value')
                ->having('COUNT(DISTINCT r.review_id) >= ?', 2)
                ->order('AVG(s.rating_summary) DESC')
                ->order('COUNT(DISTINCT r.review_id) DESC')
                /*
                 * The rotation the "Refresh" control drives — page N of the
                 * highest-rated products rather than always the same top four.
                 * Offset is clamped in hmOffset(); a value straight off the query
                 * string has no business in a LIMIT clause.
                 */
                ->limit($limit * 3, $this->hmOffset() * $limit);

            return array_map('intval', array_column($conn->fetchAll($select), 'product_id'));
        } catch (\Throwable $e) {
            $this->_logger->warning('Hub Market picked-for-you (rated): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * True when the row reflects this visitor's own browsing.
     */
    public function isPersonalised(): bool
    {
        return $this->personalised;
    }

    /**
     * The chip beside the section title.
     *
     * Reads "AI ENGINE", matching the reference, and is a LAYOUT ARGUMENT so it
     * can be changed without touching code.
     *
     * Recorded plainly, because it was argued both ways: there is no
     * recommendation model on this install — see the note at the top of this
     * class — so the badge names a capability the storefront does not have. It
     * says AI ENGINE because the reference does and because that was the
     * explicit instruction. Set `head_badge` in layout to change it; "Top rated"
     * and "From your browsing" are the accurate alternatives.
     */
    public function getHeadBadge(): string
    {
        return (string) ($this->getData('head_badge') ?: __('AI ENGINE'));
    }

    /**
     * The search-term chips above the row.
     *
     * The reference shows "YOUR SEARCHES" over five terms. Magento keeps no
     * per-visitor search history table — `report_viewed_product_index` covers
     * products viewed, not terms searched — so the terms here are the STORE's
     * most popular queries, and the label says so. Calling store-wide popular
     * terms "your searches" would be visibly wrong to a first-time visitor, who
     * is exactly the visitor most likely to see them.
     *
     * FOUR FILTERS, and none of them is decoration. This renders visitor-supplied
     * strings into the page as promoted links, so the search log is treated as
     * untrusted input, not as content:
     *
     *   num_results >= 3  a term returning one product is a dead chip, and it is
     *                     also the signature of a typo or a probe.
     *   popularity  >= 5  keeps one person's experiments out of a row that
     *                     claims to show what people search for.
     *   character allow-list  letters, digits, spaces and a little punctuation.
     *                     This store's log currently contains
     *                     `zqxq<svg/onload=alert(1)>zqxq` — an XSS probe with 204
     *                     results, three ranks below the cut. escapeHtml() in the
     *                     template makes it harmless, but rendering an attack
     *                     string as a suggested search is its own problem.
     *   test terms        `test`, `test_2`, `test new bundle` are QA artefacts on
     *                     this install and are not what anyone is shopping for.
     *
     * @return array{label: string, terms: array<int, array{term: string, url: string}>}
     */
    public function getSearchTags(): array
    {
        $out = ['label' => (string) __('Popular searches'), 'terms' => []];

        try {
            $conn = $this->hmResource->getConnection();
            $rows = $conn->fetchCol(
                $conn->select()
                    ->from($this->hmResource->getTableName('search_query'), ['query_text'])
                    ->where('store_id = ?', (int) $this->_storeManager->getStore()->getId())
                    ->where('num_results >= ?', 3)
                    ->where('popularity >= ?', 5)
                    ->where('CHAR_LENGTH(query_text) BETWEEN ? AND 24', 3)
                    ->order('popularity DESC')
                    ->limit(20)          // over-fetch; the allow-list below thins it
            );
        } catch (\Throwable $e) {
            $this->_logger->warning('Hub Market picked-for-you (search tags): ' . $e->getMessage());
            return $out;
        }

        $base = rtrim($this->getBaseUrl(), '/');
        foreach ($rows as $term) {
            $term = trim((string) $term);

            //  Allow-list, not a deny-list: anything not plainly a shopping term
            //  is dropped rather than sanitised into something that looks fine.
            if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N} \-\'&.]{2,23}$/u', $term)) {
                continue;
            }
            if (!preg_match('/\p{L}/u', $term) || preg_match('/(^|\W)test(\W|_|$)/i', $term)) {
                continue;
            }

            $out['terms'][] = [
                'term' => $term,
                'url'  => $base . '/catalogsearch/result/?q=' . rawurlencode($term),
            ];

            if (count($out['terms']) >= 5) {
                break;
            }
        }

        return $out;
    }

    /**
     * The "Refresh" control's destination.
     *
     * It reloads the page with a rotating offset, which the cache key below
     * consumes — so pressing it genuinely returns a different set rather than
     * re-rendering the same four products. A no-op control that looks like it
     * did something is worse than no control.
     */
    public function getRefreshUrl(): string
    {
        $next = ($this->hmOffset() + 1) % 5;

        return rtrim($this->getBaseUrl(), '/') . '/?hm_pick=' . $next . '#hm-picked-for-you';
    }

    /**
     * Current rotation offset, from the query string. Clamped hard: this value
     * reaches a SQL LIMIT clause and a cache key, and neither should take a
     * number straight off the URL.
     */
    private function hmOffset(): int
    {
        return max(0, min(4, (int) $this->getRequest()->getParam('hm_pick')));
    }

    /**
     * Why this product is in the row — the reference's per-card tag
     * ("You searched for ...", "Popular in Beauty & Health").
     *
     * Two honest answers, matching the two signals the row actually runs on: a
     * product from the visitor's own history says so, anything else names the
     * category it is popular in. Returns '' when neither is knowable, and the
     * template omits the tag rather than printing a placeholder.
     */
    public function getReasonFor(int $productId): string
    {
        if (isset($this->hmViewedIds[$productId])) {
            return (string) __('Recently viewed');
        }

        $category = $this->hmCategoryName($productId);

        return $category !== '' ? (string) __('Popular in %1', $category) : '';
    }

    /**
     * One batched lookup of product -> shallowest real category name.
     *
     * Shallowest by `level`, so a product filed under "All > Fashion > Dresses"
     * reads "Popular in Fashion" rather than the leaf. Level > 1 skips the root.
     */
    private function hmCategoryName(int $productId): string
    {
        if ($this->hmCategoryOf === null) {
            $this->hmCategoryOf = [];
            try {
                $conn = $this->hmResource->getConnection();
                $ids  = array_keys($this->hmViewedIds) ?: [];
                $all  = array_unique(array_merge($ids, $this->hmRenderedIds()));
                if ($all) {
                    $select = $conn->select()
                        ->from(['cp' => $this->hmResource->getTableName('catalog_category_product')],
                            ['product_id'])
                        ->join(['ce' => $this->hmResource->getTableName('catalog_category_entity')],
                            'ce.entity_id = cp.category_id', [])
                        ->join(['cv' => $this->hmResource->getTableName('catalog_category_entity_varchar')],
                            'cv.entity_id = ce.entity_id', ['name' => 'cv.value'])
                        ->join(['ea' => $this->hmResource->getTableName('eav_attribute')],
                            'ea.attribute_id = cv.attribute_id AND ea.attribute_code = "name"', [])
                        ->where('cp.product_id IN (?)', $all)
                        ->where('ce.level > ?', 1)
                        ->where('cv.store_id IN (0, ?)', (int) $this->_storeManager->getStore()->getId())
                        ->order(['cp.product_id ASC', 'ce.level ASC', 'cv.store_id DESC']);
                    foreach ($conn->fetchAll($select) as $row) {
                        $pid = (int) $row['product_id'];
                        if (!isset($this->hmCategoryOf[$pid]) && trim((string) $row['name']) !== '') {
                            $this->hmCategoryOf[$pid] = trim((string) $row['name']);
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->_logger->warning('Hub Market picked-for-you (reason): ' . $e->getMessage());
            }
        }

        return $this->hmCategoryOf[$productId] ?? '';
    }

    /**
     * Ids currently on the row, for the batched category lookup above.
     *
     * @return int[]
     */
    private function hmRenderedIds(): array
    {
        try {
            $c = $this->getProductCollection();

            return $c ? array_map('intval', array_keys($c->getItems())) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * The line under the row, opposite the "view all" link.
     */
    public function getFootNote(): string
    {
        return (string) __('Recommendations update as you shop and search');
    }

    public function getCacheKeyInfo()
    {
        $info = parent::getCacheKeyInfo();
        $info[] = 'HM_PICKED_FOR_YOU';
        $info[] = 'pick' . $this->hmOffset();

        // A personalised row MUST NOT be shared between visitors. Keying on the
        // customer/visitor id keeps one shopper's history out of another's page;
        // the impersonal fallback shares a single entry.
        try {
            $cid = $this->hmCustomerSession->getCustomerId();
            $vid = $this->hmCustomerSession->getVisitorData()['visitor_id'] ?? null;
            $info[] = $cid ? 'c' . $cid : ($vid ? 'v' . $vid : 'anon');
        } catch (\Throwable $e) {
            $info[] = 'anon';
        }

        return $info;
    }
}
