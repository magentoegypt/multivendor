<?php
/**
 * MagentoEgypt_HomeSections
 *
 * Blocks that back the Hub Market homepage sections which need real catalog data
 * rather than static CMS markup — currently the category chips with live product
 * counts.
 *
 * Deliberately minimal: no setup_version (declaring one without running
 * setup:upgrade makes DbStatusValidator 500 the whole storefront — that has
 * happened on this install), no schema, no data patches.
 */
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'MagentoEgypt_HomeSections',
    __DIR__
);
