<?php
/**
 * MagentoEgypt_SearchLanding
 *
 * The /search landing page — what the Figma reference puts behind "All
 * Categories" in the nav bar. Trending searches, the visitor's recent searches
 * and a grid of popular categories.
 *
 * Deliberately minimal: no setup_version (declaring one without running
 * setup:upgrade makes DbStatusValidator 500 the whole storefront — that has
 * happened on this install), no schema, no data patches.
 */
declare(strict_types=1);

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'MagentoEgypt_SearchLanding',
    __DIR__
);
