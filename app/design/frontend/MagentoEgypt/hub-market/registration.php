<?php
/**
 * Hub Market — MECommerce storefront theme.
 *
 * @author MagentoEgypt
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::THEME,
    'frontend/MagentoEgypt/hub-market',
    __DIR__
);
