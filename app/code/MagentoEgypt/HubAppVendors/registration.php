<?php
/**
 * MagentoEgypt_HubAppVendors — sellers for the Hub Market customer app.
 *
 * Public store (vendor) data over GraphQL: the seller list and store page
 * (hmStores, hmStore), "sold by" on products, cart lines and order lines
 * (hm_seller), other sellers' offers on a product (hm_offer_count,
 * hm_other_offers: the Vnecoms price comparison), and the FEATURED_STORES /
 * TOP_VENDORS / NEW_STORES Home sections.
 * A satellite of MagentoEgypt_HubApp: it references only core Magento types and
 * HubApp core types, never the reverse, so it can be disabled on its own.
 *
 * No setup_version: this module has no tables and no patches.
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'MagentoEgypt_HubAppVendors', __DIR__);
