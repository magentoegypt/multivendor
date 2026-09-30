<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The app asks for CustomerOrder.hm_packages only when hmAppConfig.capabilities lists `orders`, so
 * this satellite has to add itself to HubApp's capability map from its own di.xml.
 */
final class CapabilityWiringTest extends TestCase
{
    public function testDiXmlListsTheOrdersCapability(): void
    {
        $dom = new \DOMDocument();
        self::assertTrue($dom->load(dirname(__DIR__, 2) . '/etc/di.xml'));
        $xpath = new \DOMXPath($dom);
        $items = $xpath->query(
            '/config/type[@name="MagentoEgypt\HubApp\Model\Config\Capabilities"]'
            . '/arguments/argument[@name="modules"]/item'
        );

        $map = [];
        foreach ($items as $item) {
            $map[$item->getAttribute('name')] = trim($item->textContent);
        }

        self::assertSame(['orders' => 'MagentoEgypt_HubAppOrders'], $map);
    }
}
