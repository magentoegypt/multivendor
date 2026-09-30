<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Api\Home;

use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;

/**
 * Builds the content of one Home section type.
 *
 * Registered by type in MagentoEgypt\HubApp\Model\Home\SectionProviderPool
 * through etc/graphql/di.xml (argument "providers", item name = the
 * HmSectionType value, e.g. FEATURED_STORES). Satellites register their own
 * types the same way from their own graphql/di.xml.
 *
 * RULES
 * - Called inside storefront emulation of the section's store; labels built
 *   with __() come out in the store's language, theme translations included.
 * - The built Home is SHARED by everyone who asks for the same store view and
 *   audience (app cache and HTTP cache). A provider must never read the
 *   customer, the session, the cart or anything else per-viewer.
 * - Return null (or an empty result) when there is nothing to show; the
 *   section is then omitted. Never invent content.
 * - Throwing is allowed: the builder logs it and drops the section, the rest
 *   of the Home still renders.
 * - Batch: no SQL per item. Product sections return ranked, gated ids and let
 *   the `products` field load every section's products in one collection.
 */
interface SectionProviderInterface
{
    public function provide(SectionContext $context): ?SectionResult;
}
