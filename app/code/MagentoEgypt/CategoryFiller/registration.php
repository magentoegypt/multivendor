<?php
/**
 * MagentoEgypt_CategoryFiller
 *
 * CLI command that tops every Hub Market top-level category up to a minimum
 * product count. No setup_version, no schema, no data patches (see
 * MagentoEgypt_HomeSections/registration.php for why).
 */
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'MagentoEgypt_CategoryFiller',
    __DIR__
);
