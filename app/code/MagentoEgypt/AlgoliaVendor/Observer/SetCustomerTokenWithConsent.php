<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Sets Algolia's logged-in customer token cookie ONLY after cookie consent.
 *
 * Replaces two upstream observers, which etc/frontend/events.xml disables:
 *   - algoliasearch_personalization_set_user_token  (customer_login)
 *   - algoliasearch_cookie_refresher                 (controller_action_predispatch)
 *
 * The refresher runs on EVERY request for a logged-in customer and writes
 * `_ALGOLIA_MAGENTO_AUTH` without checking consent — and without even checking
 * that Insights is enabled. insights.js then reads that cookie and tags events
 * with the customer, so with cookie-restriction mode on, a signed-in customer was
 * identified to Algolia before accepting cookies. Everything else in the
 * extension (the anonymous cookie, the server-side cart / wishlist / order
 * events) already waits for consent through
 * InsightsHelper::getUserAllowedSavedCookie(); this applies that same rule here.
 *
 * Without consent a leftover cookie is removed, so withdrawing consent (or a
 * cookie set before this change) does not keep the customer identified.
 *
 * The token itself is upstream's: base64 of `customer-<sha256(email)>-<id>`, so
 * Algolia receives a pseudonym, not the address. After login, events carry both
 * this and the visitor's earlier anonymous token, which is how Algolia links a
 * guest's history to the account (DEV05 "keep the same profile").
 *
 * No constructor dependencies, deliberately: like the module's other observers
 * this has to work on a production build that has not been re-compiled, and a
 * new class with constructor arguments is not wired until setup:di:compile runs.
 */
class SetCustomerTokenWithConsent implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        try {
            $om = ObjectManager::getInstance();

            /** @var CustomerSession $session */
            $session = $om->get(CustomerSession::class);

            if (!$session->isLoggedIn()) {
                return;
            }

            /** @var InsightsHelper $insights */
            $insights = $om->get(InsightsHelper::class);
            $storeId = (int) $om->get(StoreManagerInterface::class)->getStore()->getId();

            if (!$insights->isInsightsEnabled($storeId)) {
                return;
            }

            $customer = $observer->getEvent()->getData('customer');

            if (!$customer instanceof Customer) {
                $customer = $session->getCustomer();
            }

            if ($insights->getUserAllowedSavedCookie()) {
                $insights->setAuthenticatedUserToken($customer);

                return;
            }

            /** @var CookieManagerInterface $cookies */
            $cookies = $om->get(CookieManagerInterface::class);

            if ($cookies->getCookie(InsightsHelper::ALGOLIA_CUSTOMER_USER_TOKEN_COOKIE_NAME)) {
                $cookies->deleteCookie(
                    InsightsHelper::ALGOLIA_CUSTOMER_USER_TOKEN_COOKIE_NAME,
                    $om->get(CookieMetadataFactory::class)->createCookieMetadata()->setPath('/')
                );
            }
        } catch (\Throwable $e) {
            //  Tracking must never break a page or a login.
            ObjectManager::getInstance()->get(LoggerInterface::class)
                ->warning('MagentoEgypt_AlgoliaVendor: customer token cookie: ' . $e->getMessage());
        }
    }
}
