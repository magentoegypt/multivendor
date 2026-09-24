<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Algolia\AlgoliaSearch\Observer\Insights\CheckoutOnePageControllerSuccessAction;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Algolia's "Placed order" purchase event, only when there is a shopper token.
 *
 * The event carries the anonymous userToken from the `_ALGOLIA` cookie, which
 * this theme only sets after cookie consent. For an order placed without it (a
 * shopper who declined cookies, or a headless QA checkout) upstream's observer
 * cannot send anything and logs `algolia.CRITICAL: Unable to send purchase
 * events due to Algolia events model misconfiguration` for every such order
 * (three on 2026-09-24 from TC65 test orders), which QA's critical-lines
 * metric counts as a fault. Nothing is misconfigured: not tracking without
 * consent is the requirement. So skip quietly when there is no token and hand
 * everything else to the upstream observer unchanged.
 *
 * Upstream's `algoliasearch_insights_place_order_event` is disabled by name in
 * etc/frontend/events.xml. No constructor dependencies on purpose (compiled DI,
 * see AddPriceRange).
 */
class PlaceOrderEventWithToken implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $om = ObjectManager::getInstance();

        if ($om->get(InsightsHelper::class)->getAnonymousUserToken() === '') {
            return;
        }

        $om->get(CheckoutOnePageControllerSuccessAction::class)->execute($observer);
    }
}
