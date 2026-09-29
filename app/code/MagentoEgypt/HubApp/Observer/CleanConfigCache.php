<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEgypt\HubApp\Api\CacheTagCleanerInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * admin_system_config_changed_section_hubapp (and the Algolia credentials
 * section): purge hm_app_config.
 *
 * Core only marks the full-page cache "invalidated" after a config save; a
 * cached hmAppConfig GET would keep serving the old maintenance switch or
 * version floor until someone flushes by hand. Purging the one tag is enough.
 */
class CleanConfigCache implements ObserverInterface
{
    public function __construct(private readonly CacheTagCleanerInterface $tagCleaner)
    {
    }

    public function execute(Observer $observer): void
    {
        $this->tagCleaner->clean([Tags::APP_CONFIG]);
    }
}
