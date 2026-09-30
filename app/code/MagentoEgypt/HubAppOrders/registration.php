<?php
/**
 * MagentoEgypt_HubAppOrders — an order's per-store packages for the Hub Market customer app.
 *
 * A satellite of MagentoEgypt_HubApp: CustomerOrder.hm_packages splits an order the way
 * Vnecoms_VendorsSales splits it into vendor orders (ves_vendor_sales_order), with each store's
 * status, shipments and tracking, totals and storefront-visible comments. It reads what the
 * vendor orders already hold and writes nothing.
 *
 * Disabling this module removes its GraphQL field and nothing else; no other module depends on it.
 * No setup_version (no tables of its own).
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'MagentoEgypt_HubAppOrders', __DIR__);
