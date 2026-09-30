<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MagentoEgypt\HubAppVendors\Model\Store\SellerReviews;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The reviews SellerRatings counts, narrowed to the store view that shows them.
 */
final class SellerReviewsTest extends TestCase
{
    /** @var array<int, array<int, array{0: string, 1: array<int, mixed>}>> select number => calls */
    private array $calls = [];

    public function testThePageFollowsTheRatingRuleAndTheStoreView(): void
    {
        $connection = $this->connection();
        $connection->method('fetchOne')->willReturn('3');
        $connection->expects(self::once())->method('fetchAll')->willReturn([
            ['review_id' => '41', 'product_id' => '301', 'created_at' => '2026-09-20 08:15:00',
                'nickname' => ' Sara ', 'title' => 'Great sofa ', 'detail' => ' Comfortable. '],
            //  A second detail row of the same review is not a second review.
            ['review_id' => '41', 'product_id' => '301', 'created_at' => '2026-09-20 08:15:00',
                'nickname' => 'Sara', 'title' => 'Other', 'detail' => 'Other'],
            ['review_id' => '40', 'product_id' => '302', 'created_at' => '2026-09-18 10:00:00',
                'nickname' => 'Omar', 'title' => null, 'detail' => null],
        ]);

        $page = $this->reviews($connection)->page(7, 2, 20, 1);

        self::assertSame(3, $page['total']);
        self::assertSame(
            [
                ['review_id' => 41, 'product_id' => 301, 'created_at' => '2026-09-20 08:15:00',
                    'nickname' => 'Sara', 'title' => 'Great sofa', 'detail' => 'Comfortable.'],
                ['review_id' => 40, 'product_id' => 302, 'created_at' => '2026-09-18 10:00:00',
                    'nickname' => 'Omar', 'title' => '', 'detail' => ''],
            ],
            $page['rows']
        );

        //  SellerRatings' filters — approved, the seller's product, a default-scope summary —
        //  plus the store view, on the count and on the rows alike.
        $mainWheres = [];
        $summaryWheres = [];
        foreach ($this->calls as $calls) {
            $wheres = array_values(array_filter($calls, static fn (array $call): bool => $call[0] === 'where'));
            $conditions = array_map(static fn (array $call): array => $call[1], $wheres);
            if (in_array(['s.store_id = ?', 0], $conditions, true)) {
                $summaryWheres[] = $conditions;
            } elseif ($conditions) {
                $mainWheres[] = $conditions;
            }
        }
        self::assertCount(2, $mainWheres, 'the count and the rows');
        foreach ($mainWheres as $conditions) {
            self::assertContains(['r.status_id = ?', 1], $conditions);
            self::assertContains(['pe.vendor_id = ?', 7], $conditions);
            self::assertContains(['rs.store_id = ?', 2], $conditions);
            self::assertSame('EXISTS (?)', end($conditions)[0]);
        }
        self::assertCount(2, $summaryWheres);
        self::assertContains(['s.entity_pk_value = pe.entity_id'], $summaryWheres[0]);
    }

    public function testAPageBeyondTheLastReadsNoRows(): void
    {
        $connection = $this->connection();
        $connection->method('fetchOne')->willReturn('3');
        $connection->expects(self::never())->method('fetchAll');

        self::assertSame(['total' => 3, 'rows' => []], $this->reviews($connection)->page(7, 2, 20, 2));
    }

    public function testVotesAverageToStarsAndUnvotedReviewsHaveNone(): void
    {
        $connection = $this->connection();
        $connection->method('fetchPairs')->willReturn(['41' => '90.0000', '40' => '0.0000', '39' => '60']);

        self::assertSame([41 => 4.5, 39 => 3.0], $this->reviews($connection)->ratings([41, 40, 39, 0, 41]));
        self::assertSame([], $this->reviews($this->connection())->ratings([]));
    }

    public function testAFailedQueryIsAnEmptyPageNotAnError(): void
    {
        $connection = $this->connection();
        $connection->method('fetchOne')->willThrowException(new \RuntimeException('gone'));

        self::assertSame(['total' => 0, 'rows' => []], $this->reviews($connection)->page(7, 2, 20, 1));
    }

    /**
     * @return AdapterInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function connection(): AdapterInterface
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturnCallback(function (): Select {
            $index = count($this->calls);
            $this->calls[$index] = [];
            $select = $this->createMock(Select::class);
            foreach (['from', 'join', 'where', 'columns', 'order', 'limitPage', 'group'] as $method) {
                $select->method($method)->willReturnCallback(
                    function (...$args) use ($select, $index, $method): Select {
                        //  The mock passes omitted optional arguments as their defaults.
                        while ($args && end($args) === null) {
                            array_pop($args);
                        }
                        $this->calls[$index][] = [$method, $args];

                        return $select;
                    }
                );
            }

            return $select;
        });

        return $connection;
    }

    private function reviews(AdapterInterface $connection): SellerReviews
    {
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        return new SellerReviews($resource, $this->createMock(LoggerInterface::class));
    }
}
