<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Test\Unit\Model\Package;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubAppOrders\Model\Package\StatusLabels;
use PHPUnit\Framework\TestCase;

/**
 * Status labels the way the storefront prints them: masked like core's frontend, the store view's
 * label first, the default label otherwise; one query for every status of a response.
 */
final class StatusLabelsTest extends TestCase
{
    private const STORE = 2;

    /** @var list<array<int, mixed>> where() calls */
    private array $where = [];

    private int $queries = 0;

    /** @var int[] stores the emulation ran for */
    private array $emulated = [];

    public function testTheStoreViewsLabelWinsOverTheDefault(): void
    {
        $labels = $this->labels()->labels(['processing', 'complete'], self::STORE);

        self::assertSame(['processing' => 'قيد التنفيذ', 'complete' => 'Complete'], $labels);
        self::assertSame([self::STORE], $this->emulated);
    }

    public function testPaymentReviewReadsAsProcessingLikeOnTheStorefront(): void
    {
        $labels = $this->labels()->labels(['payment_review'], self::STORE);

        self::assertSame(['payment_review' => 'قيد التنفيذ'], $labels);
        self::assertContains(['s.status IN (?)', ['processing']], $this->where);
    }

    public function testAStatusWithoutARowReadsAsItsHumanisedCode(): void
    {
        self::assertSame(
            ['out_for_delivery' => 'Out For Delivery'],
            $this->labels()->labels(['out_for_delivery'], self::STORE)
        );
    }

    public function testOneQueryForAllCodesAndNoneForCodesAlreadyRead(): void
    {
        $labels = $this->labels();

        $labels->labels(['processing', 'complete', 'processing', ' ', ''], self::STORE);
        self::assertSame(1, $this->queries);
        self::assertContains(['s.status IN (?)', ['processing', 'complete']], $this->where);

        $labels->labels(['complete'], self::STORE);
        self::assertSame(1, $this->queries);

        $labels->labels(['complete', 'canceled'], self::STORE);
        self::assertSame(2, $this->queries);
        self::assertContains(['s.status IN (?)', ['canceled']], $this->where);

        self::assertSame([], $labels->labels([], self::STORE));
        self::assertSame(2, $this->queries);
    }

    public function testTheMemoIsResetBetweenRequests(): void
    {
        $labels = $this->labels();
        $labels->labels(['complete'], self::STORE);
        $labels->_resetState();
        $labels->labels(['complete'], self::STORE);

        self::assertSame(2, $this->queries);
    }

    private function labels(): StatusLabels
    {
        $select = $this->createMock(Select::class);
        foreach (['from', 'joinLeft'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $select->method('where')->willReturnCallback(function (string $condition, $value = null) use ($select) {
            $this->where[] = [$condition, $value];

            return $select;
        });
        $rows = [
            ['status' => 'processing', 'label' => 'Processing', 'store_label' => 'قيد التنفيذ'],
            ['status' => 'complete', 'label' => 'Complete', 'store_label' => null],
            ['status' => 'canceled', 'label' => 'Canceled', 'store_label' => ' '],
        ];
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('quoteInto')->willReturnCallback(
            static fn (string $text, $value): string => str_replace('?', (string) $value, $text)
        );
        $connection->method('fetchAll')->willReturnCallback(function () use ($rows): array {
            $this->queries++;
            $wanted = (array) (end($this->where)[1] ?? []);

            return array_values(array_filter(
                $rows,
                static fn (array $row): bool => in_array($row['status'], $wanted, true)
            ));
        });
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $emulation = $this->createMock(StorefrontEmulationInterface::class);
        $emulation->method('run')->willReturnCallback(function (int $storeId, callable $callback) {
            $this->emulated[] = $storeId;

            return $callback();
        });

        return new StatusLabels($resource, $emulation);
    }
}
