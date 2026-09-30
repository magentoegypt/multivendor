<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use MagentoEgypt\HubApp\Model\Cache\ResponseTtl;
use MagentoEgypt\HubApp\Model\Config\AlgoliaKeyProvider;
use MagentoEgypt\HubApp\Model\Config\AppConfigReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * hmAppConfig: flags per platform, E.164 numbers, and the Algolia block.
 */
final class AppConfigReaderTest extends TestCase
{
    public function testFlagsForAPlatformOverrideAllPlatformRows(): void
    {
        $flags = AppConfigReader::resolveFlags([
            '_1' => ['code' => 'returns', 'enabled' => '1', 'platform' => 'all'],
            '_2' => ['code' => 'returns', 'enabled' => '0', 'platform' => 'ios'],
            '_3' => ['code' => 'push', 'enabled' => '1', 'platform' => 'android'],
            '_4' => ['code' => 'Bad Code', 'enabled' => '1', 'platform' => 'all'],
            '_5' => ['code' => 'store_credit', 'enabled' => '0'],
        ], 'IOS');

        self::assertSame(
            [['code' => 'returns', 'enabled' => false], ['code' => 'store_credit', 'enabled' => false]],
            $flags
        );
    }

    public function testWithoutPlatformOnlyAllPlatformRows(): void
    {
        $flags = AppConfigReader::resolveFlags([
            ['code' => 'push', 'enabled' => '1', 'platform' => 'android'],
            ['code' => 'returns', 'enabled' => '1', 'platform' => 'all'],
        ], null);

        self::assertSame([['code' => 'returns', 'enabled' => true]], $flags);
    }

    public function testE164(): void
    {
        self::assertSame('+971501234567', AppConfigReader::e164('+971 50 123 4567'));
        self::assertSame('+971501234567', AppConfigReader::e164('00971 50 123 4567'));
        self::assertSame('+971501234567', AppConfigReader::e164('971-50-123-4567'));
        self::assertNull(AppConfigReader::e164(' - '));
    }

    public function testAlgoliaIssuesTheSecuredKeyAndCapsTheCacheLifetime(): void
    {
        $values = [
            'hubapp/search/algolia_enabled' => '1',
            'algoliasearch_credentials/credentials/application_id' => 'HL67ED06DQ',
            'algoliasearch_credentials/credentials/search_only_api_key' => 'search-only',
            'algoliasearch_credentials/credentials/api_key' => 'admin-key',
            'algoliasearch_credentials/credentials/index_prefix' => 'hubmarket_',
        ];
        $keys = $this->createMock(AlgoliaKeyProvider::class);
        $keys->method('isPublished')->with(2)->willReturn(true);
        //  Only the store view's own indices (replicas and suggestions included).
        $keys->expects(self::once())->method('guestKey')->with('search-only', 2, 'hubmarket_ar_*')
            ->willReturn(['key' => 'c2VjdXJlZA==', 'valid_until' => 1759300000]);
        $ttl = new ResponseTtl();

        $algolia = $this->reader($values, true, $keys, $ttl)->algolia($this->store(2, 'ar'));

        self::assertSame([
            'application_id' => 'HL67ED06DQ',
            'search_api_key' => 'c2VjdXJlZA==',
            'valid_until' => 1759300000,
            'index_prefix' => 'hubmarket_',
            'product_index' => 'hubmarket_ar_products',
            'category_index' => 'hubmarket_ar_categories',
            'page_index' => 'hubmarket_ar_pages',
        ], $algolia);
        self::assertSame(AppConfigReader::ALGOLIA_KEY_CACHE_SECONDS, $ttl->getCap());
    }

    public function testAlgoliaRefusesAnAdminKeyAsSearchKey(): void
    {
        $values = [
            'hubapp/search/algolia_enabled' => '1',
            'algoliasearch_credentials/credentials/application_id' => 'APP',
            'algoliasearch_credentials/credentials/search_only_api_key' => 'same',
            'algoliasearch_credentials/credentials/api_key' => 'same',
        ];
        $keys = $this->createMock(AlgoliaKeyProvider::class);
        $keys->method('isPublished')->willReturn(true);
        $keys->expects(self::never())->method('guestKey');

        self::assertNull($this->reader($values, true, $keys, new ResponseTtl())->algolia($this->store(1, 'en')));
    }

    public function testNoAlgoliaWhenTheStorefrontPublishesNoKey(): void
    {
        $values = [
            'hubapp/search/algolia_enabled' => '1',
            'algoliasearch_credentials/credentials/application_id' => 'HL67ED06DQ',
            'algoliasearch_credentials/credentials/search_only_api_key' => 'search-only',
            'algoliasearch_credentials/credentials/api_key' => 'admin-key',
        ];
        $keys = $this->createMock(AlgoliaKeyProvider::class);
        $keys->expects(self::once())->method('isPublished')->with(1)->willReturn(false);
        $keys->expects(self::never())->method('guestKey');
        $ttl = new ResponseTtl();

        self::assertNull($this->reader($values, true, $keys, $ttl)->algolia($this->store(1, 'en')));
        self::assertNull($ttl->getCap());
    }

    public function testAlgoliaOffWhenSwitchedOffOrModuleDisabled(): void
    {
        $keys = $this->createMock(AlgoliaKeyProvider::class);
        $keys->expects(self::never())->method('guestKey');
        $values = ['algoliasearch_credentials/credentials/application_id' => 'APP'];

        self::assertNull($this->reader($values, true, $keys, new ResponseTtl())->algolia($this->store(1, 'en')));
        self::assertNull(
            $this->reader(['hubapp/search/algolia_enabled' => '1'] + $values, false, $keys, new ResponseTtl())
                ->algolia($this->store(1, 'en'))
        );
    }

    /**
     * @param array<string, string> $values
     */
    private function reader(array $values, bool $moduleEnabled, AlgoliaKeyProvider $keys, ResponseTtl $ttl): AppConfigReader
    {
        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('getValue')->willReturnCallback(static fn (string $path) => $values[$path] ?? null);
        $config->method('isSetFlag')->willReturnCallback(static fn (string $path): bool => !empty($values[$path]));
        $modules = $this->createMock(ModuleManager::class);
        $modules->method('isEnabled')->willReturn($moduleEnabled);

        return new AppConfigReader(
            $config,
            $modules,
            new Json(),
            $keys,
            $ttl,
            $this->createMock(LoggerInterface::class)
        );
    }

    private function store(int $id, string $code): StoreInterface
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn($id);
        $store->method('getCode')->willReturn($code);

        return $store;
    }
}
