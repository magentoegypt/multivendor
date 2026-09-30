<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Config;

use Magento\Framework\Module\Manager as ModuleManager;
use MagentoEgypt\HubApp\Model\Config\Capabilities;
use PHPUnit\Framework\TestCase;

/**
 * hmAppConfig.capabilities: enabled satellites only, A-Z, whatever di.xml lists.
 */
final class CapabilitiesTest extends TestCase
{
    public function testOnlyEnabledSatellitesAreListedInCodeOrder(): void
    {
        $enabled = ['MagentoEgypt_HubAppVendors', 'MagentoEgypt_HubAppAccount'];
        $modules = $this->createMock(ModuleManager::class);
        $modules->method('isEnabled')
            ->willReturnCallback(static fn (string $module): bool => in_array($module, $enabled, true));

        $capabilities = new Capabilities($modules, [
            'vendors' => 'MagentoEgypt_HubAppVendors',
            'bundle' => 'MagentoEgypt_HubAppBundle',
            'returns' => 'MagentoEgypt_HubAppReturns',
            'account' => 'MagentoEgypt_HubAppAccount',
        ]);

        self::assertSame(['account', 'vendors'], $capabilities->codes());
    }

    public function testMalformedEntriesAreSkipped(): void
    {
        $modules = $this->createMock(ModuleManager::class);
        $modules->method('isEnabled')->willReturn(true);

        $capabilities = new Capabilities($modules, [
            'Vendors ' => 'MagentoEgypt_HubAppVendors',
            'no module' => '',
            '' => 'MagentoEgypt_HubAppBundle',
            'bad code!' => 'MagentoEgypt_HubAppReturns',
        ]);

        self::assertSame(['vendors'], $capabilities->codes());
        self::assertSame([], (new Capabilities($modules))->codes());
    }

    /**
     * The shipped map names the four satellites of this repository.
     */
    public function testShippedMapListsTheFourSatellites(): void
    {
        $dom = new \DOMDocument();
        self::assertTrue($dom->load(dirname(__DIR__, 4) . '/etc/di.xml'));
        $xpath = new \DOMXPath($dom);
        $items = $xpath->query(
            '/config/type[@name="MagentoEgypt\HubApp\Model\Config\Capabilities"]'
            . '/arguments/argument[@name="modules"]/item'
        );
        $map = [];
        foreach ($items as $item) {
            $map[$item->getAttribute('name')] = trim($item->textContent);
        }

        self::assertSame(
            [
                'vendors' => 'MagentoEgypt_HubAppVendors',
                'bundle' => 'MagentoEgypt_HubAppBundle',
                'returns' => 'MagentoEgypt_HubAppReturns',
                'account' => 'MagentoEgypt_HubAppAccount',
            ],
            $map
        );
    }
}
