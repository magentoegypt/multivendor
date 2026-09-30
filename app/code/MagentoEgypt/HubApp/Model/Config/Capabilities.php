<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Config;

use Magento\Framework\Module\Manager as ModuleManager;

/**
 * hmAppConfig.capabilities: the HubApp satellites this server runs.
 *
 * Each satellite (HubAppVendors, HubAppBundle, …) can be deployed and disabled
 * on its own, and a GraphQL document that names a field of a missing satellite
 * is rejected whole. The app reads this list before it adds a satellite's
 * fields to a shared document (a product listing asking for hm_seller), so a
 * server without that satellite never sees them.
 *
 * The map (capability code => module name) comes from di.xml, so a new
 * satellite lists itself with one item in its own etc/di.xml. A module's
 * di.xml only loads while it is enabled, and ModuleManager::isEnabled() is a
 * lookup in the deployment config (app/etc/config.php): no query, nothing to
 * cache.
 */
class Capabilities
{
    /**
     * @param ModuleManager $moduleManager
     * @param array<string, string> $modules capability code => module name
     */
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly array $modules = []
    ) {
    }

    /**
     * Codes of the satellites that are enabled, A-Z.
     *
     * @return string[]
     */
    public function codes(): array
    {
        $codes = [];
        foreach ($this->modules as $code => $module) {
            $code = strtolower(trim((string) $code));
            $module = trim((string) $module);
            if ($code === '' || $module === '' || !preg_match('/^[a-z][a-z0-9_]{0,63}$/', $code)) {
                continue;
            }
            if ($this->moduleManager->isEnabled($module)) {
                $codes[$code] = $code;
            }
        }
        ksort($codes);

        return array_values($codes);
    }
}
