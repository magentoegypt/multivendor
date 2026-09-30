<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home;

use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;

/**
 * Section providers by HmSectionType value.
 *
 * Filled from etc/graphql/di.xml, argument "providers":
 *
 *   <type name="MagentoEgypt\HubApp\Model\Home\SectionProviderPool">
 *       <arguments>
 *           <argument name="providers" xsi:type="array">
 *               <item name="FEATURED_STORES" xsi:type="object">Vendor\Module\Provider</item>
 *           </argument>
 *       </arguments>
 *   </type>
 *
 * Satellites add their types from their own graphql/di.xml; DI merges the arrays.
 * A type without a provider (satellite disabled) is simply not built.
 */
class SectionProviderPool
{
    /** @var array<string, SectionProviderInterface> */
    private array $providers = [];

    /**
     * @param array<string, mixed> $providers
     */
    public function __construct(array $providers = [])
    {
        foreach ($providers as $type => $provider) {
            if ($provider instanceof SectionProviderInterface) {
                $this->providers[strtoupper((string) $type)] = $provider;
            }
        }
    }

    public function get(string $type): ?SectionProviderInterface
    {
        return $this->providers[strtoupper($type)] ?? null;
    }

    public function has(string $type): bool
    {
        return isset($this->providers[strtoupper($type)]);
    }

    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        return array_keys($this->providers);
    }
}
