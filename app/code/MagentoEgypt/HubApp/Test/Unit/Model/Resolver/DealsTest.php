<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Api\Data\StoreInterface;
use MagentoEgypt\HomeSections\Model\Ranking\DealRanker;
use MagentoEgypt\HubApp\Model\Product\DealFacts;
use MagentoEgypt\HubApp\Model\Product\RankedLists;
use MagentoEgypt\HubApp\Model\Resolver\Deals;
use MagentoEgypt\HubApp\Model\Resolver\ProductPageItems;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MagentoEgypt\HubApp\Model\Resolver\Deals
 */
class DealsTest extends TestCase
{
    /** @var DealFacts&MockObject */
    private $facts;

    private int $factReads = 0;

    public function testTheRankingPagedWithoutAnyFilter(): void
    {
        $result = $this->resolve(['pageSize' => 2, 'currentPage' => 1], ['items' => true, 'total_count' => true]);

        self::assertSame([5, 4], $result[ProductPageItems::IDS_KEY]);
        self::assertSame(4, $result['total_count']);
        self::assertSame(['page_size' => 2, 'current_page' => 1, 'total_pages' => 2], $result['page_info']);
        self::assertNull($result['categories'], 'chips only when asked for');
        self::assertSame(0, $this->factReads, 'the ranking needs no facts');
        self::assertSame('ends:5,4', $result['countdown_ends_at'], 'the soonest end on THIS page');
    }

    public function testFiltersSortsAndChipsTogether(): void
    {
        $result = $this->resolve(
            ['category_id' => 10, 'min_discount_percent' => 10, 'sort' => 'PRICE_ASC'],
            ['items' => true, 'categories' => ['id' => true]]
        );

        //  Grocery with 10%+ off, cheapest first.
        self::assertSame([3, 5], $result[ProductPageItems::IDS_KEY]);
        self::assertSame(2, $result['total_count']);
        //  The chips count every filter but the category, so Furniture stays offered.
        self::assertSame(
            [
                ['id' => 10, 'uid' => 'uid-10', 'name' => 'Grocery', 'count' => 2],
                ['id' => 20, 'uid' => 'uid-20', 'name' => 'Furniture', 'count' => 1],
            ],
            $result['categories']
        );
        self::assertSame(1, $this->factReads);
    }

    public function testEndingSoonNeedsOnlyTheRanking(): void
    {
        $result = $this->resolve(['sort' => 'ENDING_SOON'], ['items' => true]);

        self::assertSame([3, 5, 4, 2], $result[ProductPageItems::IDS_KEY]);
        self::assertSame(0, $this->factReads);
    }

    public function testMinimumDiscountOutOfRangeIsRefused(): void
    {
        $this->expectException(GraphQlInputException::class);
        $this->resolve(['min_discount_percent' => 101], ['items' => true]);
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $selection
     * @return array<string, mixed>
     */
    private function resolve(array $args, array $selection): array
    {
        $lists = $this->createMock(RankedLists::class);
        $lists->method('deals')->willReturn([
            ['id' => 5, 'type_id' => 'simple', 'percent_off' => 45.0, 'to_date' => '2026-10-03 00:00:00'],
            ['id' => 4, 'type_id' => 'new_bundle', 'percent_off' => 30.0, 'to_date' => null],
            ['id' => 3, 'type_id' => 'simple', 'percent_off' => 20.0, 'to_date' => '2026-10-01 00:00:00'],
            ['id' => 2, 'type_id' => 'simple', 'percent_off' => 5.0, 'to_date' => null],
        ]);

        $ranker = $this->createMock(DealRanker::class);
        $ranker->method('today')->willReturn('2026-09-30');
        $ranker->method('countdown')->willReturnCallback(
            static fn (array $rows): string => 'ends:' . implode(',', array_column($rows, 'id'))
        );

        $this->facts = $this->createMock(DealFacts::class);
        $this->facts->method('forProducts')->willReturnCallback(function (): array {
            $this->factReads++;

            return [
                'products' => [
                    5 => ['under' => [10], 'departments' => [10], 'created_at' => '', 'price' => 30.0],
                    4 => ['under' => [20], 'departments' => [20], 'created_at' => '', 'price' => 263.0],
                    3 => ['under' => [10, 15], 'departments' => [10], 'created_at' => '', 'price' => 12.0],
                    2 => ['under' => [10], 'departments' => [10], 'created_at' => '', 'price' => 5.0],
                ],
                'names' => [10 => 'Grocery', 20 => 'Furniture'],
            ];
        });

        $uid = $this->createMock(Uid::class);
        $uid->method('encode')->willReturnCallback(static fn (string $id): string => 'uid-' . $id);

        $info = $this->createMock(ResolveInfo::class);
        $info->method('getFieldSelection')->willReturn($selection);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $context = new class ($store) {
            public function __construct(private readonly StoreInterface $store)
            {
            }

            public function getExtensionAttributes(): object
            {
                return new class ($this->store) {
                    public function __construct(private readonly StoreInterface $store)
                    {
                    }

                    public function getStore(): StoreInterface
                    {
                        return $this->store;
                    }
                };
            }
        };

        return (new Deals($lists, $ranker, $this->facts, $uid))
            ->resolve($this->createMock(Field::class), $context, $info, null, $args);
    }
}
