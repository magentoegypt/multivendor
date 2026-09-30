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
 * magentoegypt_hero_banner_save_after / _delete_after: purge the app Home.
 *
 * The Banner model carries no cache identities of its own (the website's band
 * block is cached by its key), so the app's cached Homes would keep an edited
 * slide until they expire. One tag, hm_app_home, clears them everywhere.
 */
class CleanHomeOnHeroBannerChange implements ObserverInterface
{
    public function __construct(private readonly CacheTagCleanerInterface $tagCleaner)
    {
    }

    public function execute(Observer $observer): void
    {
        $this->tagCleaner->clean([Tags::APP_HOME]);
    }
}
