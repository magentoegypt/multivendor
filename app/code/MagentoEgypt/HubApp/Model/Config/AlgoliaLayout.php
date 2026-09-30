<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Config;

use Algolia\AlgoliaSearch\Helper\ConfigHelper as AlgoliaConfigHelper;
use Algolia\AlgoliaSearch\Helper\Configuration\AutocompleteHelper;
use Algolia\AlgoliaSearch\Helper\Configuration\InstantSearchHelper;
use Algolia\AlgoliaSearch\Service\Product\SortingTransformer;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;

/**
 * The search layout the storefront renders into window.algoliaConfig for a
 * guest (Algolia\AlgoliaSearch\Block\Configuration::getConfiguration), for
 * hmAppConfig.algolia, so the app no longer scrapes a storefront page for it.
 *
 * Read through the extension's own helpers, the same calls the block makes:
 *
 *   facets                 InstantSearchHelper::getFacets() ("other" types resolved),
 *                          store-view labels
 *   sorts                  SortingTransformer::getSortingIndices(store, guest group):
 *                          replica name, attribute, direction, label
 *   suggestion index/count autocomplete.getSuggestionsIndexName() in the storefront JS:
 *                          the Algolia Query Suggestions index when that mode is on,
 *                          <index name>_suggestions for Magento search terms, none when off
 *   currency_code          the store's current currency
 *   price_group            "default", or group_<guest group> with customer group pricing
 *   max_values_per_facet   InstantSearchHelper::getMaxValuesPerFacet()
 *   autocomplete counts    products, categories, and the "pages" section's hitsPerPage
 *   category_separator     ConfigHelper::getCategorySeparator(), as indexed
 *   categories_outside_menu ConfigHelper::showCatsNotIncludedInNavigation()
 *
 * Only asked when hmAppConfig.algolia is not null, i.e. the extension is
 * enabled and the storefront publishes a key (AppConfigReader::algolia); its
 * classes arrive as proxies (etc/di.xml). A setting that cannot be read gives
 * its empty value, never an error. Remembered per store for the request.
 */
class AlgoliaLayout implements ResetAfterRequestInterface
{
    /** Magento\Customer\Model\Group::NOT_LOGGED_IN_ID: the storefront's cached pages are guest pages. */
    public const GUEST_GROUP = 0;

    /** Algolia\AlgoliaSearch\Model\Source\Suggestions */
    private const SUGGESTIONS_MAGENTO = 1;
    private const SUGGESTIONS_ALGOLIA = 2;

    private const DEFAULT_PRICE_GROUP = 'default';

    /** @var array<int, array<string, mixed>> */
    private array $byStore = [];

    public function __construct(
        private readonly InstantSearchHelper $instantSearch,
        private readonly AutocompleteHelper $autocomplete,
        private readonly SortingTransformer $sortingTransformer,
        private readonly AlgoliaConfigHelper $algoliaConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param string $indexName the store view's base index name, e.g. hubmarket_en
     * @return array<string, mixed> HmAlgoliaConfig layout fields
     */
    public function forStore(StoreInterface $store, string $indexName): array
    {
        $storeId = (int) $store->getId();
        if (isset($this->byStore[$storeId])) {
            return $this->byStore[$storeId];
        }

        $groups = $this->read(fn (): bool => $this->algoliaConfig->isCustomerGroupsEnabled($storeId), false);
        $suggestionMode = $this->read(fn (): int => (int) $this->autocomplete->getSuggestionsMode($storeId), 0);

        return $this->byStore[$storeId] = [
            'facets' => self::facets($this->read(fn (): array => $this->instantSearch->getFacets($storeId), [])),
            'sorts' => self::sorts($this->read(
                fn (): array => $this->sortingTransformer->getSortingIndices($storeId, self::GUEST_GROUP),
                []
            )),
            ...self::suggestions(
                $suggestionMode,
                (string) $this->read(fn (): string => $this->autocomplete->getSuggestionsIndexName($storeId), ''),
                (int) $this->read(fn (): int => $this->autocomplete->getNumberOfAlgoliaSuggestions($storeId), 0),
                (int) $this->read(fn (): int => $this->autocomplete->getNumberOfQueriesSuggestions($storeId), 0),
                $indexName
            ),
            'currency_code' => (string) $this->read(fn (): string => (string) $store->getCurrentCurrencyCode(), ''),
            'price_group' => $groups ? 'group_' . self::GUEST_GROUP : self::DEFAULT_PRICE_GROUP,
            'max_values_per_facet' => max(0, (int) $this->read(fn (): int => $this->instantSearch->getMaxValuesPerFacet($storeId), 0)),
            'product_suggestions' => max(0, (int) $this->read(fn (): int => $this->autocomplete->getNumberOfProductsSuggestions($storeId), 0)),
            'category_suggestions' => max(0, (int) $this->read(fn (): int => $this->autocomplete->getNumberOfCategoriesSuggestions($storeId), 0)),
            'page_suggestions' => self::pageSuggestions($this->read(fn (): array => $this->autocomplete->getAdditionalSections($storeId), [])),
            'category_separator' => (string) $this->read(fn (): string => $this->algoliaConfig->getCategorySeparator($storeId), ''),
            'categories_outside_menu' => (bool) $this->read(fn (): bool => (bool) $this->algoliaConfig->showCatsNotIncludedInNavigation($storeId), false),
        ];
    }

    /**
     * HmAlgoliaFacet values of the admin's facet rows. Pure: unit-tested.
     *
     * @param array<int|string, mixed> $rows InstantSearchHelper::getFacets()
     * @return array<int, array{attribute: string, type: string, label: string}>
     */
    public static function facets(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $attribute = trim((string) ($row['attribute'] ?? ''));
            if ($attribute === '') {
                continue;
            }
            $type = trim((string) ($row['type'] ?? ''));
            if ($type === 'other') {
                $type = trim((string) ($row['other_type'] ?? ''));
            }
            $out[] = ['attribute' => $attribute, 'type' => $type, 'label' => trim((string) ($row['label'] ?? ''))];
        }

        return $out;
    }

    /**
     * HmAlgoliaSort values of the transformed sorting indices. Pure: unit-tested.
     *
     * @param array<int|string, mixed> $rows SortingTransformer::getSortingIndices()
     * @return array<int, array{index: string, attribute: string, direction: string, label: string}>
     */
    public static function sorts(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $index = trim((string) ($row['name'] ?? ''));
            $attribute = trim((string) ($row['attribute'] ?? ''));
            $direction = strtoupper(trim((string) ($row['sort'] ?? '')));
            if ($index === '' || $attribute === '' || !in_array($direction, ['ASC', 'DESC'], true)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? $row['sortLabel'] ?? ''));
            $out[] = ['index' => $index, 'attribute' => $attribute, 'direction' => $direction, 'label' => $label];
        }

        return $out;
    }

    /**
     * The suggestions index and count the storefront's autocomplete reads. Pure: unit-tested.
     *
     * autocomplete.js: the Algolia Query Suggestions index when that mode is on,
     * else `${indexName}_suggestions` (Magento search terms); nothing when the
     * mode is off or its own switch (AutocompleteHelper::show*Suggestions) is.
     *
     * @return array{suggestion_index: string|null, suggestion_count: int}
     */
    public static function suggestions(
        int $mode,
        string $algoliaIndex,
        int $algoliaCount,
        int $magentoCount,
        string $indexName
    ): array {
        $algoliaIndex = trim($algoliaIndex);
        if ($mode === self::SUGGESTIONS_ALGOLIA && $algoliaIndex !== '' && $algoliaCount > 0) {
            return ['suggestion_index' => $algoliaIndex, 'suggestion_count' => $algoliaCount];
        }
        if ($mode === self::SUGGESTIONS_MAGENTO && $magentoCount > 0 && $indexName !== '') {
            return ['suggestion_index' => $indexName . '_suggestions', 'suggestion_count' => $magentoCount];
        }

        return ['suggestion_index' => null, 'suggestion_count' => 0];
    }

    /**
     * hitsPerPage of the autocomplete's "pages" section; 0 without one. Pure: unit-tested.
     *
     * @param array<int|string, mixed> $sections AutocompleteHelper::getAdditionalSections()
     */
    public static function pageSuggestions(array $sections): int
    {
        foreach ($sections as $section) {
            if (is_array($section) && ($section['name'] ?? '') === 'pages') {
                return max(0, (int) ($section['hitsPerPage'] ?? 0));
            }
        }

        return 0;
    }

    /**
     * @template T
     * @param callable(): T $read
     * @param T $fallback
     * @return T
     */
    private function read(callable $read, mixed $fallback): mixed
    {
        try {
            return $read();
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: an Algolia search setting is unreadable: ' . $e->getMessage());

            return $fallback;
        }
    }

    public function _resetState(): void
    {
        $this->byStore = [];
    }
}
