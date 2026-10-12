<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\FulfillmentTracking\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The app asks for CustomerOrder.hm_fulfillment only when hmAppConfig.capabilities lists
 * `fulfillment`, so this module adds itself to HubApp's capability map from its own di.xml.
 */
final class CapabilityWiringTest extends TestCase
{
    public function testDiXmlListsTheFulfillmentCapability(): void
    {
        $dom = new \DOMDocument();
        self::assertTrue($dom->load(dirname(__DIR__, 2) . '/etc/di.xml'));
        $items = (new \DOMXPath($dom))->query(
            '/config/type[@name="MagentoEgypt\HubApp\Model\Config\Capabilities"]'
            . '/arguments/argument[@name="modules"]/item'
        );

        $map = [];
        foreach ($items as $item) {
            $map[$item->getAttribute('name')] = trim($item->textContent);
        }

        self::assertSame(['fulfillment' => 'MagentoEgypt_FulfillmentTracking'], $map);
    }
}
