<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
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
 * Algolia receives a pseudonym, not the address. That authenticated token links
 * events in Analytics only — Algolia Personalization ignores it and keys profiles
 * on the ANONYMOUS `_ALGOLIA` token alone.
 *
 * So the anonymous token is kept per customer as well (keepPersonalizationToken,
 * table magentoegypt_algolia_customer_token): the first consented request of a
 * signed-in customer records their current `_ALGOLIA`, and every later one puts
 * that token back. Upstream's CustomerLogout still deletes `_ALGOLIA` on sign-out
 * (right for a shared computer), and the profile now survives that, a second
 * device, and a cleared browser — DEV05 QA01 retest 2026-09-28, where QA's
 * logged-in shoppers would otherwise start from an empty profile after every
 * sign-in. Events browsed as a guest before signing in stay on the guest token.
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

                // Not on the sign-out request: upstream CustomerLogout deletes `_ALGOLIA`
                // there, but only when the AUTH cookie was sent, and a restore written
                // here would otherwise leave this customer's token in the browser.
                $request = $observer->getEvent()->getData('request');
                if (!$request instanceof \Magento\Framework\App\Request\Http
                    || $request->getFullActionName() !== 'customer_account_logout'
                ) {
                    $this->keepPersonalizationToken($om, $session, (int) $customer->getId());
                }

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

    private const TOKEN_TABLE = 'magentoegypt_algolia_customer_token';

    /** Session note of the token already reconciled, so most pages skip the database. */
    private const SESSION_KEY = 'hm_algolia_personalization_token';

    /** What Algolia accepts as a userToken (and what the server search plugin forwards). */
    private const TOKEN_PATTERN = '/^[A-Za-z0-9_=+\/.-]{1,129}$/';

    /**
     * One Personalization profile per customer: record the first anonymous token,
     * restore it afterwards. Called only with cookie consent.
     */
    private function keepPersonalizationToken(ObjectManager $om, CustomerSession $session, int $customerId): void
    {
        if ($customerId <= 0) {
            return;
        }

        /** @var CookieManagerInterface $cookies */
        $cookies = $om->get(CookieManagerInterface::class);
        $current = (string) $cookies->getCookie(InsightsHelper::ALGOLIA_ANON_USER_TOKEN_COOKIE_NAME);

        if (!preg_match(self::TOKEN_PATTERN, $current)) {
            $current = '';
        }

        if ($current !== '' && $session->getData(self::SESSION_KEY) === $current) {
            return;
        }

        /** @var ResourceConnection $resource */
        $resource = $om->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $table = $resource->getTableName(self::TOKEN_TABLE);
        $read = $connection->select()->from($table, 'user_token')->where('customer_id = ?', $customerId);
        $stored = (string) $connection->fetchOne($read);

        if ($stored === '') {
            if ($current === '') {
                // insights.js creates the token on this page; the next request records it.
                return;
            }

            // A shared computer: the browser may still hold ANOTHER customer's token
            // (their session expired without a sign-out, so upstream never deleted
            // it). Adopting it would merge the two profiles on every device, so this
            // customer gets a fresh token instead.
            $owner = $connection->fetchOne(
                $connection->select()->from($table, 'customer_id')->where('user_token = ?', $current)->limit(1)
            );
            $candidate = ($owner !== false && (int) $owner !== $customerId) ? self::newToken() : $current;

            // IGNORE: a concurrent first request may have recorded another token; that one wins.
            $connection->insertArray(
                $table,
                ['customer_id', 'user_token'],
                [[$customerId, $candidate]],
                AdapterInterface::INSERT_IGNORE
            );
            $stored = (string) $connection->fetchOne($read);
        }

        if ($stored !== '' && $stored !== $current) {
            // Same shape insights.js writes (path /, readable by JS, Algolia's lifetime),
            // so the library adopts it on the next page instead of making a new one.
            $durationMs = (int) $om->get(ScopeConfigInterface::class)
                ->getValue(\Algolia\AlgoliaSearch\Helper\ConfigHelper::ALGOLIA_COOKIE_DURATION);
            $metadata = $om->get(CookieMetadataFactory::class)->createPublicCookieMetadata()
                ->setDuration(max(86400, intdiv($durationMs ?: 15552000000, 1000)))
                ->setPath('/')
                ->setHttpOnly(false)
                ->setSameSite('Lax');
            $cookies->setPublicCookie(InsightsHelper::ALGOLIA_ANON_USER_TOKEN_COOKIE_NAME, $stored, $metadata);
        }

        // Varnish drops Set-Cookie on cacheable GETs, so this may take a later
        // (uncached) request to land: only mark it done once the browser agrees.
        $session->setData(self::SESSION_KEY, $stored === $current ? $stored : null);
    }

    /** A token in search-insights' own format, so the library keeps it. */
    private static function newToken(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return 'anonymous-' . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
