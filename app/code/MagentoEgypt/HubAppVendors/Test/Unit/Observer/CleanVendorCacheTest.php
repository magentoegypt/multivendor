<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Observer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Review\Model\Review;
use MagentoEgypt\HubApp\Api\CacheTagCleanerInterface;
use MagentoEgypt\HubAppVendors\Observer\CleanVendorCache;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A review purges the seller caches when it is or was approved: its approval
 * moves the seller's rating, its text is on the seller's Reviews tab.
 */
final class CleanVendorCacheTest extends TestCase
{
    private const PENDING = 2;
    private const NOT_APPROVED = 3;

    public function testOnlyAReviewThatIsOrWasApprovedPurges(): void
    {
        //  [event, status before (null: new), status after, purges?]
        $cases = [
            'guest review submitted' => ['review_save_after', null, self::PENDING, false],
            'review approved' => ['review_save_after', self::PENDING, Review::STATUS_APPROVED, true],
            'created approved in admin' => ['review_save_after', null, Review::STATUS_APPROVED, true],
            'approved review edited' => ['review_save_after', Review::STATUS_APPROVED, Review::STATUS_APPROVED, true],
            'pending review edited' => ['review_save_after', self::PENDING, self::PENDING, false],
            'approval withdrawn' => ['review_save_after', Review::STATUS_APPROVED, self::NOT_APPROVED, true],
            'pending review rejected' => ['review_save_after', self::PENDING, self::NOT_APPROVED, false],
            'approved review deleted' => ['review_delete_after', Review::STATUS_APPROVED, Review::STATUS_APPROVED, true],
            'pending review deleted' => ['review_delete_after', self::PENDING, self::PENDING, false],
        ];

        foreach ($cases as $label => [$event, $before, $after, $purges]) {
            $purged = [];
            $cleaner = $this->createMock(CacheTagCleanerInterface::class);
            $cleaner->method('clean')->willReturnCallback(static function (array $tags) use (&$purged): void {
                $purged[] = $tags;
            });

            $this->observer($cleaner)->execute($this->event($event, $this->review($before, $after)));

            self::assertSame($purges ? [['hm_vendor', 'hm_vendor_7']] : [], $purged, $label);
        }
    }

    public function testASellerSaveStillPurges(): void
    {
        $cleaner = $this->createMock(CacheTagCleanerInterface::class);
        $cleaner->expects(self::once())->method('clean')->with(['hm_vendor', 'hm_vendor_12']);

        $this->observer($cleaner)->execute($this->event('vendor_save_after', new DataObject(['id' => 12])));
    }

    private function observer(CacheTagCleanerInterface $cleaner): CleanVendorCache
    {
        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturn('7');
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        return new CleanVendorCache($cleaner, $resource, $this->createMock(LoggerInterface::class));
    }

    private function review(?int $before, int $after): Review
    {
        $data = ['status_id' => (string) $after, 'entity_pk_value' => '301'];
        $review = $this->createMock(Review::class);
        $review->method('getData')->willReturnCallback(static fn ($key = '') => $data[$key] ?? null);
        $review->method('getOrigData')->willReturnCallback(
            static fn ($key = null) => $key === 'status_id' && $before !== null ? (string) $before : null
        );

        return $review;
    }

    private function event(string $name, DataObject $object): Observer
    {
        return new Observer(['event' => new Event(['name' => $name, 'data_object' => $object, 'object' => $object])]);
    }
}
