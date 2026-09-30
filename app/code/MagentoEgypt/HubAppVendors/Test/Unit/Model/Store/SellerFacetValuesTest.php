<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Module\Manager as ModuleManager;
use MagentoEgypt\AlgoliaVendor\Model\SellerResolver;
use MagentoEgypt\HubApp\Model\Seller\SellerDirectory;
use MagentoEgypt\HubApp\Model\Seller\SellerName;
use MagentoEgypt\HubAppVendors\Model\Store\SellerFacetValues;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * facet_value: the seller attribute AlgoliaVendor puts on product records.
 */
final class SellerFacetValuesTest extends TestCase
{
    /** ves_vendor_entity rows as this install has them (company, url key). */
    private const SELLERS = [
        3 => ['company' => 'Enara', 'key' => 'ENARA'],
        4 => ['company' => ' Loly Store ', 'key' => 'loly'],
        5 => ['company' => '', 'key' => 'test_1'],
        6 => ['company' => '0', 'key' => 'V8S2'],
        7 => ['company' => '.', 'key' => 'V2S2'],
        8 => ['company' => 'ميا كو', 'key' => 'MIA'],
        9 => ['company' => '', 'key' => 'hub-market.seller_two'],
    ];

    /**
     * The names SellerDirectory translates are the raw names AddSellerData translates: the
     * same text through the same __() of the same store view gives the same facet value.
     */
    public function testTheNameRuleIsTheIndexersRule(): void
    {
        if (!class_exists(SellerResolver::class)) {
            self::markTestSkipped('AlgoliaVendor is not in this codebase.');
        }
        $rows = [];
        foreach (self::SELLERS as $id => $seller) {
            $rows[] = ['entity_id' => (string) $id, 'status' => '2', 'company' => $seller['company'], 'vendor_id' => $seller['key']];
        }
        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchAll')->willReturn($rows);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);
        $resolver = new SellerResolver($resource, $this->createMock(LoggerInterface::class));

        foreach (self::SELLERS as $id => $seller) {
            self::assertSame(
                $resolver->getRawName($id),
                SellerName::source($seller['company'], $seller['key']),
                'seller ' . $id
            );
        }
    }

    public function testTheValueIsTheSellersNameInTheStoreView(): void
    {
        $directory = $this->createMock(SellerDirectory::class);
        $directory->method('getApproved')->willReturnCallback(
            static fn (int $id): ?array => in_array($id, [8, 9], true) ? ['id' => $id] : null
        );
        $directory->method('names')->willReturnCallback(
            static fn (array $ids, int $storeId): array => array_intersect_key(
                [8 => $storeId === 2 ? 'ميا كو' : 'MIA CO', 9 => ''],
                array_flip($ids)
            )
        );

        $values = new SellerFacetValues($this->modules(true), $directory);

        self::assertSame('MIA CO', $values->forVendor(8, 1));
        self::assertSame('ميا كو', $values->forVendor(8, 2));
        self::assertNull($values->forVendor(9, 1), 'no name, no seller attribute');
        self::assertNull($values->forVendor(3, 1), 'not approved: not indexed');
        self::assertNull($values->forVendor(0, 1));
    }

    public function testWithoutAlgoliaVendorRecordsCarryNoSeller(): void
    {
        $directory = $this->createMock(SellerDirectory::class);
        $directory->expects(self::never())->method('names');

        self::assertNull((new SellerFacetValues($this->modules(false), $directory))->forVendor(8, 1));
    }

    private function modules(bool $algoliaVendor): ModuleManager
    {
        $modules = $this->createMock(ModuleManager::class);
        $modules->method('isEnabled')->with('MagentoEgypt_AlgoliaVendor')->willReturn($algoliaVendor);

        return $modules;
    }
}
