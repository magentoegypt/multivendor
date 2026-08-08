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
                ->limit($limit * 3);

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

    public function getCacheKeyInfo()
    {
        $info = parent::getCacheKeyInfo();
        $info[] = 'HM_PICKED_FOR_YOU';

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
