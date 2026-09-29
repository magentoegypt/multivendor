<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Config;

use Algolia\AlgoliaSearch\Helper\ConfigHelper as AlgoliaConfigHelper;
use Algolia\AlgoliaSearch\Service\AlgoliaConnector;
use Psr\Log\LoggerInterface;

/**
 * The Algolia search key exactly as the storefront hands it to a guest browser.
 *
 * window.algoliaConfig.apiKey is not the raw search-only key: the extension's
 * Block\Configuration builds it with
 *
 *     AlgoliaConnector::generateSearchSecuredApiKey(
 *         ConfigHelper::getSearchOnlyAPIKey($storeId),
 *         ConfigHelper::getAttributesToFilter($customerGroupId),
 *         $storeId
 *     )
 *
 * a SECURED key derived from the search-only key, with tagFilters and
 * validUntil = now + 24 h. This calls the same two methods for the guest group
 * (the storefront's full-page-cached pages are guest pages), so the app gets
 * the same restrictions the website's search has, and reads validUntil back
 * out of the key rather than guessing it.
 *
 * getAttributesToFilter() collects its filters through the
 * algolia_get_attributes_to_filter event, whose one observer (catalog
 * permissions) is frontend-only and a no-op without Adobe Commerce catalog
 * permissions; in the graphql area it therefore adds nothing, as on this store.
 *
 * The Algolia classes arrive as proxies (etc/di.xml): nothing of the extension
 * is instantiated unless a key is actually issued.
 */
class AlgoliaKeyProvider
{
    /** Magento\Customer\Model\Group::NOT_LOGGED_IN_ID */
    public const GUEST_GROUP = 0;

    public function __construct(
        private readonly AlgoliaConnector $algoliaConnector,
        private readonly AlgoliaConfigHelper $algoliaConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{key: string, valid_until: int|null}|null null when no key can be issued
     */
    public function guestKey(string $searchOnlyKey, int $storeId): ?array
    {
        try {
            $filters = $this->algoliaConfig->getAttributesToFilter(self::GUEST_GROUP);
            $key = (string) $this->algoliaConnector->generateSearchSecuredApiKey(
                $searchOnlyKey,
                is_array($filters) ? $filters : [],
                $storeId
            );
        } catch (\Throwable $e) {
            $this->logger->error('HubApp: could not issue the Algolia search key: ' . $e->getMessage());

            return null;
        }
        if ($key === '') {
            return null;
        }

        return ['key' => $key, 'valid_until' => self::validUntil($key)];
    }

    /**
     * validUntil of an Algolia secured API key, or null when it has none.
     *
     * A secured key is base64( hex HMAC-SHA256 (64 chars) . url-encoded restrictions ).
     */
    public static function validUntil(string $securedKey): ?int
    {
        $decoded = base64_decode($securedKey, true);
        if ($decoded === false || strlen($decoded) <= 64) {
            return null;
        }
        parse_str(substr($decoded, 64), $restrictions);
        $validUntil = $restrictions['validUntil'] ?? null;

        return is_scalar($validUntil) && ctype_digit((string) $validUntil) ? (int) $validUntil : null;
    }
}
