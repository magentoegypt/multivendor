<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use MagentoEgypt\HubApp\Model\Cache\ResponseTtl;
use Psr\Log\LoggerInterface;

/**
 * hmAppConfig: the app settings of one store view (HmAppConfig shape).
 *
 * Everything comes from Stores > Configuration > Hub Market App (hubapp/*),
 * read at store scope so store-view texts, website channels and global
 * switches each fall back the usual way; plus the storefront's own Algolia
 * application, index names and the secured search key the storefront gives
 * guest browsers (AlgoliaKeyProvider).
 */
class AppConfigReader
{
    public const PLATFORMS = ['ANDROID', 'IOS'];

    /** Platform values of a feature-flag row (admin dynamic rows). */
    public const FLAG_PLATFORM_ALL = 'all';
    public const FLAG_PLATFORM_ANDROID = 'android';
    public const FLAG_PLATFORM_IOS = 'ios';

    private const MAX_TRENDING = 10;

    /** Algolia extension config (Algolia\AlgoliaSearch\Helper\ConfigHelper). */
    private const ALGOLIA_MODULE = 'Algolia_AlgoliaSearch';
    private const ALGOLIA_APPLICATION_ID = 'algoliasearch_credentials/credentials/application_id';
    private const ALGOLIA_SEARCH_KEY = 'algoliasearch_credentials/credentials/search_only_api_key';
    private const ALGOLIA_ADMIN_KEY = 'algoliasearch_credentials/credentials/api_key';
    private const ALGOLIA_INDEX_PREFIX = 'algoliasearch_credentials/credentials/index_prefix';

    /**
     * How long the HTTP cache may keep a response that carries an Algolia key.
     * The key is valid 24 hours from when it was issued; an hour of caching
     * leaves the app a key with at least 23 hours to go.
     */
    public const ALGOLIA_KEY_CACHE_SECONDS = 3600;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ModuleManager $moduleManager,
        private readonly Json $json,
        private readonly AlgoliaKeyProvider $algoliaKeys,
        private readonly ResponseTtl $responseTtl,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param string|null $platform ANDROID, IOS or null for both
     * @return array<string, mixed>
     */
    public function read(StoreInterface $store, ?string $platform): array
    {
        $storeId = (int) $store->getId();
        $platform = $platform !== null && in_array(strtoupper($platform), self::PLATFORMS, true)
            ? strtoupper($platform)
            : null;

        return [
            'store_code' => (string) $store->getCode(),
            'locale' => (string) $this->value('general/locale/code', $storeId),
            'search' => [
                'hint' => $this->text('hubapp/search/hint', $storeId),
                'trending_terms' => $this->trending($storeId),
            ],
            'algolia' => $this->algolia($store),
            'contact' => $this->contact($storeId),
            'version' => $this->versions($storeId, $platform),
            'maintenance' => [
                'enabled' => $this->scopeConfig->isSetFlag('hubapp/maintenance/enabled', ScopeInterface::SCOPE_STORE, $storeId),
                'message' => $this->text('hubapp/maintenance/message', $storeId),
                'retry_after_minutes' => $this->positiveInt('hubapp/maintenance/retry_after_minutes', $storeId),
            ],
            'features' => $this->features($storeId, $platform),
        ];
    }

    /**
     * @return string[]
     */
    private function trending(int $storeId): array
    {
        $raw = (string) $this->value('hubapp/search/trending_terms', $storeId);
        $terms = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $term = trim($line);
            if ($term === '' || in_array($term, $terms, true)) {
                continue;
            }
            $terms[] = mb_substr($term, 0, 80);
            if (count($terms) >= self::MAX_TRENDING) {
                break;
            }
        }

        return $terms;
    }

    /**
     * The storefront's Algolia application, index names and search key, or null.
     *
     * The key is the SECURED key the storefront gives a guest browser (derived
     * from the search-only key, tagFilters, validUntil = now + 24 h), issued
     * through the extension's own code (AlgoliaKeyProvider) — never the raw
     * search-only key and never the admin key. Because it expires, a response
     * carrying it is kept in the HTTP cache for at most
     * ALGOLIA_KEY_CACHE_SECONDS (ResponseTtl), so a cached copy always has
     * about a day left; the app refreshes hmAppConfig before valid_until.
     *
     * Index names are built the way the extension builds them
     * (IndexNameFetcher: prefix + store code + suffix), so the app searches
     * exactly the indices the storefront does, e.g. hubmarket_ar_products.
     *
     * @return array<string, string|int|null>|null
     */
    public function algolia(StoreInterface $store): ?array
    {
        $storeId = (int) $store->getId();
        if (!$this->scopeConfig->isSetFlag('hubapp/search/algolia_enabled', ScopeInterface::SCOPE_STORE, $storeId)) {
            return null;
        }
        if (!$this->moduleManager->isEnabled(self::ALGOLIA_MODULE)) {
            return null;
        }

        $applicationId = trim((string) $this->value(self::ALGOLIA_APPLICATION_ID, $storeId));
        $searchKey = trim((string) $this->value(self::ALGOLIA_SEARCH_KEY, $storeId));
        if ($applicationId === '' || $searchKey === '') {
            return null;
        }

        //  A search-only key pasted into the admin key field (or the reverse): a
        //  secured key derived from an admin key inherits its rights. Refuse.
        $adminKey = trim((string) $this->value(self::ALGOLIA_ADMIN_KEY, $storeId));
        if ($adminKey !== '' && hash_equals($adminKey, $searchKey)) {
            $this->logger->error(
                'HubApp: the Algolia search-only key equals the admin API key; not exposing it to the app.'
            );

            return null;
        }

        $secured = $this->algoliaKeys->guestKey($searchKey, $storeId);
        if ($secured === null) {
            return null;
        }
        $this->responseTtl->cap(self::ALGOLIA_KEY_CACHE_SECONDS);

        $prefix = trim((string) $this->value(self::ALGOLIA_INDEX_PREFIX, $storeId));
        $base = $prefix . (string) $store->getCode();

        return [
            'application_id' => $applicationId,
            'search_api_key' => $secured['key'],
            'valid_until' => $secured['valid_until'],
            'index_prefix' => $prefix,
            'product_index' => $base . '_products',
            'category_index' => $base . '_categories',
            'page_index' => $base . '_pages',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function contact(int $storeId): array
    {
        $whatsapp = self::e164((string) $this->value('hubapp/contact/whatsapp', $storeId));
        $phone = self::e164((string) $this->value('hubapp/contact/phone', $storeId));
        $email = trim((string) $this->value('hubapp/contact/email', $storeId));

        return [
            'whatsapp_number' => $whatsapp,
            'whatsapp_url' => $whatsapp !== null ? 'https://wa.me/' . ltrim($whatsapp, '+') : null,
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
            'hours' => $this->text('hubapp/contact/hours', $storeId),
        ];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function versions(int $storeId, ?string $platform): array
    {
        $message = $this->text('hubapp/version/message', $storeId);
        $out = [];
        foreach (self::PLATFORMS as $candidate) {
            if ($platform !== null && $candidate !== $platform) {
                continue;
            }
            $key = strtolower($candidate);
            $out[] = [
                'platform' => $candidate,
                'min_version' => $this->text("hubapp/version/{$key}_min", $storeId),
                'latest_version' => $this->text("hubapp/version/{$key}_latest", $storeId),
                'store_url' => $this->text("hubapp/version/{$key}_store_url", $storeId),
                'message' => $message,
            ];
        }

        return $out;
    }

    /**
     * Flags that apply to $platform. With no platform, only "all platforms" rows.
     *
     * @return array<int, array{code: string, enabled: bool}>
     */
    public function features(int $storeId, ?string $platform): array
    {
        $raw = $this->value('hubapp/features/flags', $storeId);
        $rows = [];
        if (is_string($raw) && trim($raw) !== '') {
            try {
                $decoded = $this->json->unserialize($raw);
                $rows = is_array($decoded) ? $decoded : [];
            } catch (\Throwable $e) {
                $this->logger->warning('HubApp: feature flags are not valid JSON: ' . $e->getMessage());
            }
        } elseif (is_array($raw)) {
            $rows = $raw;
        }

        return self::resolveFlags($rows, $platform);
    }

    /**
     * Pure flag resolution, for the unit test.
     *
     * @param array<int|string, mixed> $rows dynamic rows (code / enabled / platform)
     * @return array<int, array{code: string, enabled: bool}> sorted by code
     */
    public static function resolveFlags(array $rows, ?string $platform): array
    {
        $platform = $platform !== null ? strtolower($platform) : null;
        $general = [];
        $specific = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = strtolower(trim((string) ($row['code'] ?? '')));
            if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $code)) {
                continue;
            }
            $enabled = in_array(strtolower(trim((string) ($row['enabled'] ?? '0'))), ['1', 'true', 'yes'], true);
            $rowPlatform = strtolower(trim((string) ($row['platform'] ?? self::FLAG_PLATFORM_ALL)));
            if ($rowPlatform === '' || $rowPlatform === self::FLAG_PLATFORM_ALL) {
                $general[$code] = $enabled;
            } elseif ($platform !== null && $rowPlatform === $platform) {
                $specific[$code] = $enabled;
            }
        }

        $merged = array_merge($general, $specific);
        ksort($merged);
        $out = [];
        foreach ($merged as $code => $enabled) {
            $out[] = ['code' => (string) $code, 'enabled' => $enabled];
        }

        return $out;
    }

    /**
     * "+971 50 123 4567", "00971501234567", "971501234567" -> "+971501234567"; null when no digits.
     */
    public static function e164(string $number): ?string
    {
        $number = trim($number);
        if ($number === '') {
            return null;
        }
        $plus = str_starts_with($number, '+');
        $digits = (string) preg_replace('/\D+/', '', $number);
        if ($digits === '') {
            return null;
        }
        if (!$plus && str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return '+' . $digits;
    }

    private function text(string $path, int $storeId): ?string
    {
        $value = trim((string) $this->value($path, $storeId));

        return $value !== '' ? $value : null;
    }

    private function positiveInt(string $path, int $storeId): ?int
    {
        $value = (int) $this->value($path, $storeId);

        return $value > 0 ? $value : null;
    }

    private function value(string $path, int $storeId): mixed
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
