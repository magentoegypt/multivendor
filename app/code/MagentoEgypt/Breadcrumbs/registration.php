<?php
/**
 * MagentoEgypt_Breadcrumbs
 *
 * Puts the product's category back into the product-page breadcrumb.
 *
 * Magento only shows it when `current_category` happens to be in the registry —
 * which on this install is never, even when the shopper arrives from the
 * category listing — so every product read "Home / Product" where the design
 * calls for "Home / Category / Product".
 *
 * Deliberately minimal: one plugin, no schema, and NO setup_version (declaring
 * one without running setup:upgrade makes DbStatusValidator 500 the whole
 * storefront — that has happened on this install).
 */
declare(strict_types=1);

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'MagentoEgypt_Breadcrumbs',
    __DIR__
);
