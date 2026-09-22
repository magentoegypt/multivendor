<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use MagentoEgypt\AlgoliaVendor\Model\SellerResolver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Keeps products belonging to pending, disabled and expired sellers out of the
 * Algolia index.
 *
 * Magento's own catalogue visibility does not cover this: a seller can be
 * suspended while their products stay enabled and in stock, so OpenSearch and
 * Algolia would both keep selling them. Vnecoms hides them on its own pages but
 * has no idea a second search index exists.
 *
 * Filtering here — before the collection loads — rather than dropping records
 * later means the excluded products are never fetched, never priced and never
 * built into records.
 */
class ExcludeInactiveSellerProducts implements ObserverInterface
{
    public function __construct(private readonly SellerResolver $sellers)
    {
    }

    public function execute(Observer $observer): void
    {
        $collection = $observer->getData('collection');

        if (!$collection instanceof \Magento\Eav\Model\Entity\Collection\AbstractCollection) {
            return;
        }

        /*
         * vendor_id is static, so this resolves to a column on `e` rather than a
         * join. The record builder reads it back off the product in
         * AddSellerData; without this the field is simply absent from the
         * collection's select and every product indexes as seller-less.
         */
        try {
            $collection->addAttributeToSelect('vendor_id');
        } catch (\Throwable $e) {
            // A collection that will not take the column still indexes fine,
            // just without seller data. Not worth failing a reindex over.
        }

        /*
         * Product approval, applied unconditionally and independently of the
         * seller gate below. An approved seller can still have products awaiting
         * approval, and those must not be searchable — OpenSearch excludes them
         * via VendorExtend's IndexerHandler plugin, which is Elasticsearch-only
         * and therefore silent for Algolia. Without this, 10 unapproved products
         * were live in hubmarket_*_products.
         */
        $approvalGate = $this->sellers->getApprovalGateSql();

        if ($approvalGate !== null) {
            $collection->getSelect()->where($approvalGate);
        }

        $nonLive = $this->sellers->getNonLiveIds();

        if (!$nonLive) {
            return;
        }

        /*
         * vendor_id IS NULL / 0 is an admin-created product with no seller —
         * 133 of them on this install — and must stay in the index. An id that
         * matches no seller row is left in too: an orphan is a data bug, and
         * quietly dropping products is the more expensive failure.
         */
        $collection->getSelect()->where(
            'e.vendor_id IS NULL OR e.vendor_id = 0 OR e.vendor_id NOT IN (?)',
            $nonLive
        );
    }
}
