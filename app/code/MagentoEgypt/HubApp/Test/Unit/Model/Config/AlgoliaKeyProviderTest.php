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
        $key = self::securedKey('restrictIndices=hubmarket_ar_%2A&tagFilters=&validUntil=1759300000');
        $config = $this->createMock(ConfigHelper::class);
        $config->expects(self::once())->method('getAttributesToFilter')->with(AlgoliaKeyProvider::GUEST_GROUP)
            ->willReturn(['filters' => 'catalog_permissions.customer_group_0 != 0']);
        $connector = $this->createMock(AlgoliaConnector::class);
        //  The storefront's restrictions untouched (the connector adds tagFilters and validUntil), plus the indices.
        $connector->expects(self::once())->method('generateSearchSecuredApiKey')
            ->with('search-only', ['filters' => 'catalog_permissions.customer_group_0 != 0', 'restrictIndices' => 'hubmarket_ar_*'], 2)
            ->willReturn($key);

        $provider = new AlgoliaKeyProvider($connector, $config, $this->createMock(LoggerInterface::class));

        self::assertSame(['key' => $key, 'valid_until' => 1759300000], $provider->guestKey('search-only', 2, 'hubmarket_ar_*'));
    }

    public function testNoKeyWhenTheExtensionFails(): void
    {
        $config = $this->createMock(ConfigHelper::class);
        $config->method('getAttributesToFilter')->willReturn([]);
        $connector = $this->createMock(AlgoliaConnector::class);
        $connector->method('generateSearchSecuredApiKey')->willThrowException(new \RuntimeException('no credentials'));

        $provider = new AlgoliaKeyProvider($connector, $config, $this->createMock(LoggerInterface::class));

        self::assertNull($provider->guestKey('search-only', 1, 'hubmarket_en_*'));
    }

    public function testPublishedOnlyWhereTheStorefrontRendersAlgolia(): void
    {
        //  [front end, application id, admin key, autocomplete, instant search, published?]
        $cases = [
            'all on' => [true, 'APP', 'admin', true, true, true],
            'autocomplete only' => [true, 'APP', 'admin', true, false, true],
            'instant search only' => [true, 'APP', 'admin', false, true, true],
            'front end disabled' => [false, 'APP', 'admin', true, true, false],
            'no application id' => [true, '', 'admin', true, true, false],
            'no admin key' => [true, 'APP', null, true, true, false],
            'no search interface' => [true, 'APP', 'admin', false, false, false],
        ];
        foreach ($cases as $label => [$frontEnd, $application, $adminKey, $autocomplete, $instant, $published]) {
            $config = $this->createMock(ConfigHelper::class);
            $config->method('isEnabledFrontEnd')->with(3)->willReturn($frontEnd);
            $config->method('getApplicationID')->willReturn($application);
            $config->method('getAPIKey')->willReturn($adminKey);
            $config->method('isAutoCompleteEnabled')->willReturn($autocomplete);
            $config->method('isInstantEnabled')->willReturn($instant);

            $provider = new AlgoliaKeyProvider(
                $this->createMock(AlgoliaConnector::class),
                $config,
                $this->createMock(LoggerInterface::class)
            );

            self::assertSame($published, $provider->isPublished(3), $label);
        }
    }
}
