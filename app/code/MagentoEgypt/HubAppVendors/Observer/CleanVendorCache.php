<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Observer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Review\Model\Review;
use MagentoEgypt\HubApp\Api\CacheTagCleanerInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use Psr\Log\LoggerInterface;

/**
 * Purges the app's seller data (hm_vendor, hm_vendor_<id>) when it changes, so a
 * cached GET of hmStores, hmStore or a product's hm_seller is never staler than
 * the website.
 *
 *   vendor_save_after / vendor_delete_after            the seller (name, status, is_home,
 *                                                      dispatch_time): Vnecoms Vendor model
 *   vendor_config_save_after / vendor_config_delete_after  logo, banner, policies: the
 *                                                      seller panel's settings (VendorsConfig)
 *   review_save_after / review_delete_after            ratings of the reviewed product's seller,
 *                                                      only when the review's approval changes
 *
 * Only APPROVED reviews count in a seller's rating and review count
 * (SellerRatings), so a review purges only when it becomes approved, stops
 * being approved, or is deleted while approved. Guest reviews arrive pending:
 * submitting one purges nothing, approving it does.
 *
 * Global events: sellers change from admin, the seller panel and the seller app
 * (REST). Product-driven changes (a product approved, disabled) move listable
 * counts; the core module's cron purges hm_vendor for those in batches.
 *
 * Never throws: a failed purge is logged, the save goes through.
 */
class CleanVendorCache implements ObserverInterface
{
    public function __construct(
        private readonly CacheTagCleanerInterface $cacheTagCleaner,
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            $event = $observer->getEvent();
            $object = $event->getData('data_object') ?? $event->getData('object');
            if (!$object instanceof DataObject) {
                return;
            }

            $name = (string) $event->getName();
            if (str_starts_with($name, 'review_')) {
                if (!self::approvalChanged($object, str_ends_with($name, '_delete_after'))) {
                    return;     // pending, rejected or still approved: no seller figure moves
                }
                $vendorId = $this->productVendor((int) $object->getData('entity_pk_value'));
                if ($vendorId < 1) {
                    return;     // a review of a Hub Market product changes no seller
                }
            } elseif (str_starts_with($name, 'vendor_config_')) {
                $vendorId = (int) $object->getData('vendor_id');
            } else {
                $vendorId = (int) $object->getId();
            }

            $tags = [Tags::VENDOR];
            if ($vendorId > 0) {
                $tags[] = Tags::vendor($vendorId);
            }
            $this->cacheTagCleaner->clean($tags);
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller cache purge failed: ' . $e->getMessage());
        }
    }

    /**
     * Whether a saved or deleted review moves a seller's approved-review figures.
     *
     * Saved: status_id moved to or from APPROVED (compared with the loaded
     * value; a new review has none). Deleted: it was approved.
     */
    public static function approvalChanged(DataObject $review, bool $deleted): bool
    {
        $approved = (int) $review->getData('status_id') === Review::STATUS_APPROVED;
        $wasApproved = $review instanceof AbstractModel
            && (int) $review->getOrigData('status_id') === Review::STATUS_APPROVED;

        return $deleted ? ($approved || $wasApproved) : $approved !== $wasApproved;
    }

    private function productVendor(int $productId): int
    {
        if ($productId < 1) {
            return 0;
        }
        $connection = $this->resource->getConnection();

        return (int) $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('catalog_product_entity'), ['vendor_id'])
                ->where('entity_id = ?', $productId)
        );
    }
}
