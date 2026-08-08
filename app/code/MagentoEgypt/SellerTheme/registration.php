<?php
/**
 * MagentoEgypt_SellerTheme
 *
 * Hub Market design layer for the Vnecoms seller panel (Magento area "vendors").
 *
 * Why a module and not a theme: the vendors-area theme is hardcoded in
 * vendor/vnecoms/module-vendors/etc/di.xml —
 *
 *   <type name="Magento\Theme\Model\View\Design">
 *     <argument name="themes"><item name="vendors">Vnecoms/vendor</item></argument>
 *
 * so it cannot be switched from the admin, and switching it in di.xml would force a
 * setup:di:compile on a live production site. Instead this module contributes CSS
 * through the theme's own collector directive, which needs neither:
 *
 *   vendor/vnecoms/theme-vendors-default/web/css/styles-m.less
 *     //@magento_import 'source/_module.less';
 *
 * Vnecoms_VendorsPageBuilder already uses that same hook, so the mechanism is proven
 * in this install.
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'MagentoEgypt_SellerTheme',
    __DIR__
);
