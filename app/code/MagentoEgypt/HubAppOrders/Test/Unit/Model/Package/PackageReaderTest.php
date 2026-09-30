<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Test\Unit\Model\Package;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MagentoEgypt\HubAppOrders\Model\Package\PackageReader;
use PHPUnit\Framework\TestCase;

/**
 * The reads behind hm_packages: one query per table for a whole page of orders (no per-order
 * queries), and only the rows the website would show a customer.
 */
final class PackageReaderTest extends TestCase
{
    /** @var list<array{table: string, where: list<array<int, mixed>>}> every select, in order */
    private array $selects = [];

    /** @var \SplObjectStorage<Select, int> */
    private \SplObjectStorage $index;

    public function testNoOrderNoQuery(): void
    {
        $result = $this->reader([])->read([]);

        self::assertSame([], $this->selects);
        self::assertSame([], $result['vendor_orders']);
        self::assertSame([], $result['comments']);
    }

    public function testOneQueryPerTableForAPageOfOrders(): void
    {
        $this->reader($this->tables())->read([10, 11, 12, 10]);

        self::assertSame(
            [
                'ves_vendor_sales_order',
                'sales_order_item',
                'sales_shipment',
                'sales_shipment_item',
                'sales_shipment_track',
                'sales_order_status_history',
            ],
            array_column($this->selects, 'table')
        );
        foreach ([0, 1, 2, 4] as $query) {
            self::assertContains(['order_id IN (?)', [10, 11, 12]], $this->selects[$query]['where']);
        }
    }

    public function testOnlyTheLinesOfShipmentsWithoutTheirVendorOrderAreRead(): void
    {
        //  801 was made from vendor order 501 of order 10; 802 names none; 803 names order 10's
        //  vendor order but belongs to order 11.
        $this->reader($this->tables())->read([10, 11]);

        self::assertSame([['parent_id IN (?)', [802, 803]]], $this->selects[3]['where']);
    }

    public function testCommentsAreTheVisibleOnesOfTheVendorOrders(): void
    {
        $this->reader($this->tables())->read([10, 11]);

        $where = $this->selects[5]['where'];
        self::assertContains(['parent_id IN (?)', [10, 11]], $where);
        self::assertContains(['vendor_order_status IN (?)', [501, 502]], $where);
        self::assertContains(['is_visible_on_front = ?', 1], $where);
        self::assertContains(['comment IS NOT NULL', null], $where);
    }

    public function testAnOrderWithoutVendorOrdersOrShipmentsReadsTwoTables(): void
    {
        $this->reader(['sales_order_item' => [['item_id' => '101', 'order_id' => '12']]])->read([12]);

        self::assertSame(
            ['ves_vendor_sales_order', 'sales_order_item', 'sales_shipment'],
            array_column($this->selects, 'table')
        );
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function tables(): array
    {
        return [
            'ves_vendor_sales_order' => [
                ['entity_id' => '501', 'order_id' => '10', 'vendor_id' => '7'],
                ['entity_id' => '502', 'order_id' => '10', 'vendor_id' => '9'],
            ],
            'sales_order_item' => [['item_id' => '101', 'order_id' => '10']],
            'sales_shipment' => [
                ['entity_id' => '801', 'order_id' => '10', 'vendor_order_id' => '501'],
                ['entity_id' => '802', 'order_id' => '10', 'vendor_order_id' => '0'],
                ['entity_id' => '803', 'order_id' => '11', 'vendor_order_id' => '502'],
            ],
        ];
    }

    /**
     * @param array<string, list<array<string, mixed>>> $tables table => rows any select of it returns
     */
    private function reader(array $tables): PackageReader
    {
        $this->index = new \SplObjectStorage();
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturnCallback(function (): Select {
            $select = $this->createMock(Select::class);
            $position = count($this->selects);
            $this->selects[] = ['table' => '', 'where' => []];
            $this->index[$select] = $position;
            $select->method('from')->willReturnCallback(function ($name) use ($select, $position): Select {
                $this->selects[$position]['table'] = is_array($name) ? (string) reset($name) : (string) $name;

                return $select;
            });
            $select->method('where')->willReturnCallback(
                function ($condition, $value = null) use ($select, $position): Select {
                    $this->selects[$position]['where'][] = [$condition, $value];

                    return $select;
                }
            );
            $select->method('order')->willReturnSelf();

            return $select;
        });
        $connection->method('fetchAll')->willReturnCallback(function (Select $select) use ($tables): array {
            return $tables[$this->selects[$this->index[$select]]['table']] ?? [];
        });
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        return new PackageReader($resource);
    }
}
