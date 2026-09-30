<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Locale\ListsInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\Renderer\Placeholder;
use Magento\Framework\Phrase\RendererInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubAppVendors\Model\Store\SellerProfile;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * What the store page prints under the seller's name: phone, location, sales.
 */
final class SellerProfileTest extends TestCase
{
    private const ROW = [
        'telephone' => ' +971 50 123 4567 ',
        'street' => '12 Al Wasl Rd',
        'city' => 'دبي ',
        'region' => '',
        'region_id' => '562',
        'country_id' => 'AE',
        'postcode' => '',
    ];

    protected function tearDown(): void
    {
        Phrase::setRenderer(new Placeholder());
    }

    public function testThePhoneFollowsTheAdminSwitch(): void
    {
        self::assertSame('+971 50 123 4567', $this->profile(['vendors/profile/show_phone' => '1'])->phone(7));
        self::assertNull($this->profile(['vendors/profile/show_phone' => '0'])->phone(7));
        //  Nothing a tel: link could dial.
        self::assertNull($this->profile(['vendors/profile/show_phone' => '1'], ['telephone' => ' - '] + self::ROW)->phone(7));
        self::assertNull($this->profile(['vendors/profile/show_phone' => '1'], null)->phone(7));
    }

    public function testTheSalesCountFollowsTheAdminSwitch(): void
    {
        self::assertSame(1240, $this->profile(['vendors/profile/show_sales_count' => '1'], self::ROW, '1240')->salesCount(7));
        self::assertSame(0, $this->profile(['vendors/profile/show_sales_count' => '1'], self::ROW, '0')->salesCount(7));
        self::assertNull($this->profile(['vendors/profile/show_sales_count' => '0'], self::ROW, '1240')->salesCount(7));
        self::assertNull($this->profile(['vendors/profile/show_sales_count' => '1'], self::ROW, false)->salesCount(7));
    }

    public function testTheLocationIsTheWebsitesLineInTheStoreViewsLanguage(): void
    {
        $renderer = $this->createMock(RendererInterface::class);
        $renderer->method('render')->willReturnCallback(
            static fn (array $source): string => ['دبي' => 'Dubai'][end($source)] ?? end($source)
        );
        Phrase::setRenderer($renderer);

        $profile = $this->profile(
            ['general/locale/code' => 'en_US', 'vendors/profile/address_template' => '{{var city}}, {{var region}}, {{var country}}'],
            self::ROW,
            '0',
            ['id' => 562, 'country' => 'AE', 'name' => 'Dubai']
        );

        self::assertSame('Dubai, Dubai, United Arab Emirates', $profile->location(7, 1));
    }

    public function testWithoutATemplateTheDefaultOneIsUsed(): void
    {
        //  The region id belongs to another country: the typed region stays, as on the website.
        $profile = $this->profile(
            ['general/locale/code' => 'ar_SA'],
            ['region' => '999999999', 'region_id' => '0'] + self::ROW,
            '0',
            ['id' => 999999999, 'country' => 'EG', 'name' => 'Giza']
        );

        self::assertSame('999999999, الإمارات العربية المتحدة', $profile->location(7, 2));
        self::assertNull($this->profile([], null)->location(7, 2));
    }

    /**
     * @param array<string, string> $config
     * @param array<string, string>|null $row
     * @param string|false $sales the count, or false: no sales table
     * @param array{id: int, country: string, name: string}|null $region
     */
    private function profile(array $config, ?array $row = self::ROW, string|false $sales = '0', ?array $region = null): SellerProfile
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(static fn (string $path) => $config[$path] ?? null);

        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchRow')->willReturn($row ?? false);
        $connection->method('isTableExists')->willReturn($sales !== false);
        $connection->method('fetchOne')->willReturn($sales === false ? false : $sales);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $lists = $this->createMock(ListsInterface::class);
        $lists->method('getCountryTranslation')->willReturnCallback(
            static fn (string $id, ?string $locale): string => [
                'en_US' => ['AE' => 'United Arab Emirates'],
                'ar_SA' => ['AE' => 'الإمارات العربية المتحدة'],
            ][$locale][$id] ?? $id
        );

        $regionModel = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId', 'getName'])
            ->addMethods(['getCountryId'])
            ->getMock();
        $regionModel->method('load')->willReturnSelf();
        $regionModel->method('getId')->willReturn($region['id'] ?? null);
        $regionModel->method('getCountryId')->willReturn($region['country'] ?? null);
        $regionModel->method('getName')->willReturn($region['name'] ?? null);
        $regions = $this->createMock(RegionFactory::class);
        $regions->method('create')->willReturn($regionModel);

        $emulation = $this->createMock(StorefrontEmulationInterface::class);
        $emulation->method('run')->willReturnCallback(static fn (int $storeId, callable $callback) => $callback());

        return new SellerProfile(
            $resource,
            $scopeConfig,
            $lists,
            $regions,
            $emulation,
            $this->createMock(LoggerInterface::class)
        );
    }
}
