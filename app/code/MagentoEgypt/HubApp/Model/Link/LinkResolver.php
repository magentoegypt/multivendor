<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Link;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use Psr\Log\LoggerInterface;

/**
 * HmLink values for configured destinations. See LinkResolverInterface for the contract.
 *
 * Batched: one url_rewrite query per resolveMany() (plus one for product url
 * keys, one for CMS identifiers and one for bare brand keys, each only when
 * needed), so a whole Home's worth of links costs a handful of queries.
 */
class LinkResolver implements LinkResolverInterface, ResetAfterRequestInterface
{
    private const SELLER_ROUTE_PATH = 'vendors/vendorspage/url_key';
    private const BRAND_ROUTE_PATH = 'brand/general_settings/route';
    private const PRODUCT_SUFFIX_PATH = 'catalog/seo/product_url_suffix';

    /** @var array<int, LinkClassifier> */
    private array $classifiers = [];

    /** @var array<int, string> */
    private array $linkBase = [];

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ResourceConnection $resource,
        private readonly EavConfig $eavConfig,
        private readonly Uid $uid,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(?string $target, int $storeId): ?array
    {
        return $this->resolveMany(['t' => $target], $storeId)['t'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function resolveMany(array $targets, int $storeId): array
    {
        $out = [];
        $classified = [];
        $lookups = [];

        try {
            $classifier = $this->classifier($storeId);
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: link classifier unavailable: ' . $e->getMessage());
            foreach ($targets as $key => $target) {
                $out[$key] = null;
            }

            return $out;
        }

        foreach ($targets as $key => $target) {
            $result = $classifier->classify($target);
            $classified[$key] = $result;
            if ($result !== null && $result['type'] === LinkClassifier::LOOKUP) {
                $lookups[(string) $result['path']] = true;
            }
        }

        $found = $lookups ? $this->lookup(array_keys($lookups), $storeId, $classifier) : [];

        foreach ($classified as $key => $result) {
            if ($result === null) {
                $out[$key] = null;
                continue;
            }
            if ($result['type'] === LinkClassifier::LOOKUP) {
                $out[$key] = $found[(string) $result['path']]
                    ?? $this->build(self::TYPE_EXTERNAL, $result['path'], $result['query'], null, null, $storeId);
                continue;
            }
            if ($result['type'] === self::TYPE_EXTERNAL && $result['external'] !== null) {
                $out[$key] = [
                    'type' => self::TYPE_EXTERNAL,
                    'url' => $result['external'],
                    'path' => null,
                    'uid' => null,
                    'code' => null,
                ];
                continue;
            }
            $out[$key] = $this->build(
                $result['type'],
                $result['path'],
                $result['query'],
                null,
                $result['code'],
                $storeId
            );
        }

        return $out;
    }

    /**
     * @inheritDoc
     */
    public function category(int $categoryId, ?string $requestPath, int $storeId): array
    {
        $path = trim((string) $requestPath, '/');
        if ($path === '') {
            $path = 'catalog/category/view/id/' . $categoryId;
        }

        return $this->build(self::TYPE_CATEGORY, $path, '', $this->uid->encode((string) $categoryId), null, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function product(int $productId, string $urlKey, ?string $requestPath, int $storeId): array
    {
        $path = trim((string) $requestPath, '/');
        if ($path === '') {
            $path = $urlKey !== ''
                ? $urlKey . (string) $this->scopeConfig->getValue(self::PRODUCT_SUFFIX_PATH, ScopeInterface::SCOPE_STORE, $storeId)
                : 'catalog/product/view/id/' . $productId;
        }

        return $this->build(
            self::TYPE_PRODUCT,
            $path,
            '',
            $this->uid->encode((string) $productId),
            $urlKey !== '' ? $urlKey : null,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function store(string $sellerCode, int $storeId): array
    {
        $route = $this->config(self::SELLER_ROUTE_PATH, $storeId);
        $path = ($route !== '' ? $route . '/' : '') . $sellerCode;

        return $this->build(self::TYPE_STORE, $path, '', null, $sellerCode, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function brand(string $urlKey, int $storeId): array
    {
        $route = $this->config(self::BRAND_ROUTE_PATH, $storeId);
        $path = ($route !== '' ? $route . '/' : '') . $urlKey;

        return $this->build(self::TYPE_BRAND, $path, '', null, $urlKey, $storeId);
    }

    /**
     * @return array<string, string|null>
     */
    private function build(
        string $type,
        ?string $path,
        string $query,
        ?string $uid,
        ?string $code,
        int $storeId
    ): array {
        $relative = ltrim((string) $path, '/') . ($query !== '' ? '?' . $query : '');

        return [
            'type' => $type,
            'url' => $this->linkBase($storeId) . $relative,
            'path' => $path !== null ? $relative : null,
            'uid' => $uid,
            'code' => $code,
        ];
    }

    /**
     * url_rewrite for many request paths at once; custom redirects followed one level.
     *
     * @param string[] $paths
     * @return array<string, array<string, string|null>> request path => link
     */
    private function lookup(array $paths, int $storeId, LinkClassifier $classifier): array
    {
        $rows = $this->rewrites($paths, $storeId);

        //  A custom rewrite points somewhere else: classify its target once more.
        $second = [];
        foreach ($rows as $requestPath => $row) {
            if ($row['entity_type'] === 'custom' && trim((string) $row['target_path']) !== '') {
                $second[$requestPath] = $classifier->classify((string) $row['target_path']);
            }
        }
        $secondPaths = [];
        foreach ($second as $result) {
            if ($result !== null && $result['type'] === LinkClassifier::LOOKUP) {
                $secondPaths[(string) $result['path']] = true;
            }
        }
        $secondRows = $secondPaths ? $this->rewrites(array_keys($secondPaths), $storeId) : [];

        $productIds = [];
        $pageIds = [];
        foreach (array_merge(array_values($rows), array_values($secondRows)) as $row) {
            if ($row['entity_type'] === 'product') {
                $productIds[(int) $row['entity_id']] = true;
            } elseif ($row['entity_type'] === 'cms-page') {
                $pageIds[(int) $row['entity_id']] = true;
            }
        }
        $urlKeys = $productIds ? $this->productUrlKeys(array_keys($productIds), $storeId) : [];
        $identifiers = $pageIds ? $this->cmsIdentifiers(array_keys($pageIds)) : [];

        $out = [];
        foreach ($paths as $path) {
            $row = $rows[$path] ?? null;
            if ($row === null) {
                continue;
            }
            if ($row['entity_type'] === 'custom') {
                $target = $second[$path] ?? null;
                if ($target === null) {
                    continue;
                }
                if ($target['type'] === LinkClassifier::LOOKUP) {
                    $targetRow = $secondRows[(string) $target['path']] ?? null;
                    $link = $targetRow ? $this->fromRewrite($targetRow, $urlKeys, $identifiers, $storeId) : null;
                } elseif ($target['type'] === self::TYPE_EXTERNAL && $target['external'] !== null) {
                    $link = [
                        'type' => self::TYPE_EXTERNAL,
                        'url' => $target['external'],
                        'path' => null,
                        'uid' => null,
                        'code' => null,
                    ];
                } else {
                    $link = $this->build($target['type'], $target['path'], $target['query'], null, $target['code'], $storeId);
                }
                if ($link !== null) {
                    $out[$path] = $link;
                }
                continue;
            }
            $link = $this->fromRewrite($row, $urlKeys, $identifiers, $storeId);
            if ($link !== null) {
                $out[$path] = $link;
            }
        }

        //  A bare brand url_key ("apple") is routed by MGS_Brand's own router.
        $missing = array_values(array_filter($paths, static fn (string $p): bool
            => !isset($out[$p]) && !str_contains($p, '/')));
        if ($missing) {
            foreach ($this->brandKeys($missing) as $key) {
                foreach ($missing as $path) {
                    if (strcasecmp($path, $key) === 0 || strcasecmp((string) preg_replace('~\.html?$~i', '', $path), $key) === 0) {
                        $out[$path] = $this->build(self::TYPE_BRAND, $path, '', null, $key, $storeId);
                    }
                }
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row url_rewrite row
     * @param array<int, string> $urlKeys
     * @param array<int, string> $identifiers
     * @return array<string, string|null>|null
     */
    private function fromRewrite(array $row, array $urlKeys, array $identifiers, int $storeId): ?array
    {
        $id = (int) $row['entity_id'];
        //  A 301 from an old URL still names the entity; link to its current path.
        $path = (int) $row['redirect_type'] > 0 && trim((string) $row['target_path']) !== ''
            ? (string) $row['target_path']
            : (string) $row['request_path'];

        switch ($row['entity_type']) {
            case 'category':
                return $this->build(self::TYPE_CATEGORY, $path, '', $this->uid->encode((string) $id), null, $storeId);
            case 'product':
                return $this->build(
                    self::TYPE_PRODUCT,
                    $path,
                    '',
                    $this->uid->encode((string) $id),
                    $urlKeys[$id] ?? null,
                    $storeId
                );
            case 'cms-page':
                return $this->build(self::TYPE_CMS_PAGE, $path, '', null, $identifiers[$id] ?? $path, $storeId);
            default:
                return null;
        }
    }

    /**
     * @param string[] $paths
     * @return array<string, array<string, mixed>> request path (as asked) => row
     */
    private function rewrites(array $paths, int $storeId): array
    {
        if (!$paths) {
            return [];
        }
        try {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(
                        $this->resource->getTableName('url_rewrite'),
                        ['request_path', 'entity_type', 'entity_id', 'target_path', 'redirect_type']
                    )
                    ->where('store_id = ?', $storeId)
                    ->where('request_path IN (?)', $paths)
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: url_rewrite lookup failed: ' . $e->getMessage());

            return [];
        }

        //  Key by the path as ASKED (the column collation is case-insensitive).
        $byLower = [];
        foreach ($rows as $row) {
            $byLower[strtolower((string) $row['request_path'])] = $row;
        }
        $out = [];
        foreach ($paths as $path) {
            if (isset($byLower[strtolower($path)])) {
                $out[$path] = $byLower[strtolower($path)];
            }
        }

        return $out;
    }

    /**
     * @param int[] $productIds
     * @return array<int, string>
     */
    private function productUrlKeys(array $productIds, int $storeId): array
    {
        try {
            $attributeId = (int) $this->eavConfig->getAttribute(Product::ENTITY, 'url_key')->getId();
            if (!$attributeId) {
                return [];
            }
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from($this->resource->getTableName('catalog_product_entity_varchar'), ['entity_id', 'store_id', 'value'])
                    ->where('attribute_id = ?', $attributeId)
                    ->where('store_id IN (?)', [0, $storeId])
                    ->where('entity_id IN (?)', $productIds)
                    ->order('store_id ASC')
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: product url keys unavailable: ' . $e->getMessage());

            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            //  Ordered by store: the store view's value overwrites the default.
            if (trim((string) $row['value']) !== '') {
                $out[(int) $row['entity_id']] = (string) $row['value'];
            }
        }

        return $out;
    }

    /**
     * @param int[] $pageIds
     * @return array<int, string>
     */
    private function cmsIdentifiers(array $pageIds): array
    {
        try {
            $connection = $this->resource->getConnection();

            return array_map('strval', $connection->fetchPairs(
                $connection->select()
                    ->from($this->resource->getTableName('cms_page'), ['page_id', 'identifier'])
                    ->where('page_id IN (?)', $pageIds)
            ));
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: CMS identifiers unavailable: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Enabled MGS brand url keys among $candidates (".html" ignored).
     *
     * @param string[] $candidates
     * @return string[]
     */
    private function brandKeys(array $candidates): array
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('mgs_brand');
            if (!$connection->isTableExists($table)) {
                return [];
            }
            $keys = array_values(array_unique(array_map(
                static fn (string $c): string => (string) preg_replace('~\.html?$~i', '', $c),
                $candidates
            )));

            return array_map('strval', $connection->fetchCol(
                $connection->select()
                    ->from($table, ['url_key'])
                    ->where('status = ?', 1)
                    ->where('url_key IN (?)', $keys)
            ));
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: brand keys unavailable: ' . $e->getMessage());

            return [];
        }
    }

    private function classifier(int $storeId): LinkClassifier
    {
        if (!isset($this->classifiers[$storeId])) {
            $store = $this->storeManager->getStore($storeId);
            $bases = [];
            foreach ([UrlInterface::URL_TYPE_LINK, UrlInterface::URL_TYPE_WEB] as $type) {
                foreach ([true, false] as $secure) {
                    $bases[] = (string) $store->getBaseUrl($type, $secure);
                }
            }
            $this->classifiers[$storeId] = new LinkClassifier(
                $bases,
                (string) $store->getCode(),
                $this->config(self::SELLER_ROUTE_PATH, $storeId),
                $this->config(self::BRAND_ROUTE_PATH, $storeId)
            );
        }

        return $this->classifiers[$storeId];
    }

    private function linkBase(int $storeId): string
    {
        if (!isset($this->linkBase[$storeId])) {
            try {
                $base = (string) $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_LINK, true);
            } catch (\Throwable $e) {
                $this->logger->warning('HubApp: store base URL unavailable: ' . $e->getMessage());
                $base = '';
            }
            $this->linkBase[$storeId] = $base === '' ? '' : rtrim($base, '/') . '/';
        }

        return $this->linkBase[$storeId];
    }

    private function config(string $path, int $storeId): string
    {
        return trim((string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId), '/ ');
    }

    /**
     * Per-request memo only; nothing survives a request in a long-running process.
     */
    public function _resetState(): void
    {
        $this->classifiers = [];
        $this->linkBase = [];
    }
}
