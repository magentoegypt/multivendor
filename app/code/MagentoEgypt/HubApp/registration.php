<?php
/**
 * MagentoEgypt_HubApp — GraphQL for the Hub Market customer app.
 *
 * The core of the HubApp family: the shared services (links, media URLs,
 * storefront emulation, ranked product loading, cache tags), the shared GraphQL
 * types, the app Home (table, admin, resolvers), the app settings and the
 * deals / best sellers / bundles / brands lists. The satellites
 * (HubAppVendors, HubAppBundle, HubAppReturns, HubAppAccount) build on the
 * interfaces in Api/ and never the other way round.
 *
 * No setup_version: db_schema.xml and data patches need none, and declaring one
 * without a matching setup_module row makes DbStatusValidator 500 the site.
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'MagentoEgypt_HubApp', __DIR__);
