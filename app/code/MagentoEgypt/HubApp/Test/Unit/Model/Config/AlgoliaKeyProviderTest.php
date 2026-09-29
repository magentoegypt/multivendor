<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Config;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Service\AlgoliaConnector;
use MagentoEgypt\HubApp\Model\Config\AlgoliaKeyProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The guest key goes through the extension's own methods; validUntil is read back from it.
 */
final class AlgoliaKeyProviderTest extends TestCase
{
    /**
     * A key built like Algolia's SearchClient::generateSecuredApiKey().
     */
    private static function securedKey(string $restrictions): string
    {
        return base64_encode(hash_hmac('sha256', $restrictions, 'parent-key') . $restrictions);
    }

    public function testValidUntilIsReadFromTheKey(): void
    {
        self::assertSame(1759300000, AlgoliaKeyProvider::validUntil(self::securedKey('tagFilters=&validUntil=1759300000')));
        self::assertNull(AlgoliaKeyProvider::validUntil(self::securedKey('tagFilters=')));
        self::assertNull(AlgoliaKeyProvider::validUntil('0123456789abcdef0123456789abcdef'));
        self::assertNull(AlgoliaKeyProvider::validUntil('not base64 at all!'));
    }

    public function testGuestKeyUsesTheGuestGroupFiltersAndTheSearchOnlyKey(): void
    {
        $key = self::securedKey('tagFilters=&validUntil=1759300000');
        $config = $this->createMock(ConfigHelper::class);
        $config->expects(self::once())->method('getAttributesToFilter')->with(AlgoliaKeyProvider::GUEST_GROUP)->willReturn([]);
        $connector = $this->createMock(AlgoliaConnector::class);
        $connector->expects(self::once())->method('generateSearchSecuredApiKey')->with('search-only', [], 2)->willReturn($key);

        $provider = new AlgoliaKeyProvider($connector, $config, $this->createMock(LoggerInterface::class));

        self::assertSame(['key' => $key, 'valid_until' => 1759300000], $provider->guestKey('search-only', 2));
    }

    public function testNoKeyWhenTheExtensionFails(): void
    {
        $config = $this->createMock(ConfigHelper::class);
        $config->method('getAttributesToFilter')->willReturn([]);
        $connector = $this->createMock(AlgoliaConnector::class);
        $connector->method('generateSearchSecuredApiKey')->willThrowException(new \RuntimeException('no credentials'));

        $provider = new AlgoliaKeyProvider($connector, $config, $this->createMock(LoggerInterface::class));

        self::assertNull($provider->guestKey('search-only', 1));
    }
}
