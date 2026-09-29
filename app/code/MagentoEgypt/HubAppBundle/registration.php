<?php
/**
 * MagentoEgypt_HubAppBundle — `new_bundle` for the Hub Market customer app.
 *
 * BundleExtend's `new_bundle` runs on Magento's own bundle type model, but the
 * GraphQL layer only knows `bundle`: cart and wishlist queries threw for a
 * `new_bundle` line and the bundle fields of products and orders came back
 * empty. This module maps `new_bundle` everywhere BundleGraphQl maps `bundle`
 * (read side) and adds hmAddBundleToCart, which can carry the per-selection
 * configurable choices the website posts and core's cart inputs cannot express.
 *
 * Depends on BundleExtend and core GraphQL modules only, not on MagentoEgypt_HubApp.
 * No setup_version: no tables, no patches.
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'MagentoEgypt_HubAppBundle', __DIR__);
