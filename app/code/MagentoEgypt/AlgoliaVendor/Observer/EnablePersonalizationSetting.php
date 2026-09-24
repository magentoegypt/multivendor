<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Keeps `enablePersonalization: true` in the product index settings (DEV05).
 *
 * Algolia's Advanced Personalization validator flags "Personalization Disabled
 * — the enablePersonalization parameter isn't set in the index settings".
 * Setting it once from the dashboard/API does not last here: a full product
 * reindex builds `<index>_tmp` from the extension's own settings and moves it
 * over the live index, which drops any key the extension does not send. Adding
 * it to the settings the extension pushes makes it survive every reindex.
 *
 * Harmless for visitors without consent: the storefront mixin
 * (Algolia_AlgoliaSearch/js/insights-hm-mixin.js) sends
 * enablePersonalization=false with any search that has no userToken, and
 * server-side searches carry no events-backed token.
 *
 * No constructor dependencies on purpose (compiled DI, see AddPriceRange).
 */
class EnablePersonalizationSetting implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $transport = $observer->getData('index_settings');

        if (!$transport instanceof \Magento\Framework\DataObject) {
            return;
        }

        $transport->setData('enablePersonalization', true);
    }
}
