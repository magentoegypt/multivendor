<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Arabic queries on the English storefront found nothing (2026-09-24, DEV06
 * "typos, AR/EN mixed queries"): "سامسونج", "موبايل", "حقيبة" returned 0 hits on
 * hubmarket_en_products because an English record only carries English text,
 * while the Arabic storefront already answers English queries (English brand
 * and product words sit in its records).
 *
 * Two hooks, one class:
 *  - algolia_after_create_product_object: records of a NON-Arabic store get
 *    `name_ar` (the product's name on the Arabic store view of the same
 *    website) and `categories_ar` (its categories' Arabic names). Both are
 *    left out when they would only repeat the English text.
 *  - algolia_products_index_before_set_settings: for a non-Arabic store's
 *    index, make them searchable just BELOW their English counterparts (so an
 *    English match still ranks first). queryLanguages is left alone: the
 *    extension does not send it, and Arabic matches without it.
 *
 * Arabic values are read at the Arabic store view with a fallback to the
 * default scope, which suits both category-name conventions on this install
 * (older categories: store 0 English / store 1 Arabic; 101-111: store 0 Arabic).
 *
 * No constructor dependencies on purpose (compiled DI, see AddPriceRange).
 */
class ArabicTermsForEnglishIndex implements ObserverInterface
{
    private const SETTINGS_EVENT = 'algolia_products_index_before_set_settings';

    /** @var array<int, int|null> store id => Arabic store id of the same website (null = none / is Arabic) */
    private static array $arabicStoreFor = [];

    /** @var array<int, array<int, string>> Arabic store id => category id => Arabic name */
    private static array $categoryNames = [];

    public function execute(Observer $observer): void
    {
        if ($observer->getEvent()->getName() === self::SETTINGS_EVENT) {
            $this->addSearchableAttributes($observer);

            return;
        }

        $transport = $observer->getData('custom_data');
        $product = $observer->getData('productObject');

        if (!$transport instanceof \Magento\Framework\DataObject || $product === null) {
            return;
        }

        $arabicStoreId = $this->arabicStoreFor((int) $product->getStoreId());

        if ($arabicStoreId === null) {
            return;
        }

        $connection = ObjectManager::getInstance()->get(ResourceConnection::class)->getConnection();
        $nameAr = $this->valueAtStore($connection, 'catalog_product_entity_varchar', 4, 'name', (int) $product->getId(), $arabicStoreId);

        if ($nameAr !== '' && $nameAr !== (string) $transport->getData('name')) {
            $transport->setData('name_ar', $nameAr);
        }

        $english = array_map('strval', (array) $transport->getData('categories_without_path'));
        $categoriesAr = [];

        foreach ((array) ($transport->getData('categoryIds') ?: $product->getCategoryIds()) as $categoryId) {
            $label = $this->categoryName($connection, (int) $categoryId, $arabicStoreId);
            if ($label !== '' && !in_array($label, $english, true)) {
                $categoriesAr[] = $label;
            }
        }

        if ($categoriesAr) {
            $transport->setData('categories_ar', array_values(array_unique($categoriesAr)));
        }
    }

    private function addSearchableAttributes(Observer $observer): void
    {
        $transport = $observer->getData('index_settings');

        if (!$transport instanceof \Magento\Framework\DataObject
            || $this->arabicStoreFor((int) $observer->getData('store_id')) === null
        ) {
            return;
        }

        $settings = $transport->getData();
        $searchable = $settings['searchableAttributes'] ?? null;

        if (!is_array($searchable)) {
            return;
        }

        $searchable = self::insertAfter($searchable, 'name', 'unordered(name_ar)');
        $searchable = self::insertAfter($searchable, 'categories_without_path', 'unordered(categories_ar)');
        $settings['searchableAttributes'] = $searchable;
        $transport->setData($settings);
    }

    /**
     * Insert $entry right after the searchable entry for $after (e.g.
     * "unordered(name)"); at the end when $after is absent; never twice.
     *
     * @param list<string> $searchable
     * @return list<string>
     */
    private static function insertAfter(array $searchable, string $after, string $entry): array
    {
        if (in_array($entry, $searchable, true)) {
            return $searchable;
        }

        foreach ($searchable as $i => $existing) {
            if (preg_match('/^(?:[a-z]+\()?' . preg_quote($after, '/') . '\)?$/i', trim((string) $existing))) {
                array_splice($searchable, $i + 1, 0, [$entry]);

                return array_values($searchable);
            }
        }

        $searchable[] = $entry;

        return array_values($searchable);
    }

    private function arabicStoreFor(int $storeId): ?int
    {
        if (array_key_exists($storeId, self::$arabicStoreFor)) {
            return self::$arabicStoreFor[$storeId];
        }

        $om = ObjectManager::getInstance();
        $config = $om->get(ScopeConfigInterface::class);
        $isArabic = static fn(int $id): bool => str_starts_with(
            (string) $config->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $id),
            'ar'
        );
        $found = null;

        try {
            if (!$isArabic($storeId)) {
                $storeManager = $om->get(StoreManagerInterface::class);
                $websiteId = (int) $storeManager->getStore($storeId)->getWebsiteId();
                foreach ($storeManager->getStores() as $store) {
                    if ((int) $store->getWebsiteId() === $websiteId && $isArabic((int) $store->getId())) {
                        $found = (int) $store->getId();
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            $found = null;
        }

        return self::$arabicStoreFor[$storeId] = $found;
    }

    private function categoryName($connection, int $categoryId, int $arabicStoreId): string
    {
        if (!isset(self::$categoryNames[$arabicStoreId])) {
            self::$categoryNames[$arabicStoreId] = [];
        }

        if (!array_key_exists($categoryId, self::$categoryNames[$arabicStoreId])) {
            self::$categoryNames[$arabicStoreId][$categoryId] = $this->valueAtStore(
                $connection,
                'catalog_category_entity_varchar',
                3,
                'name',
                $categoryId,
                $arabicStoreId
            );
        }

        return self::$categoryNames[$arabicStoreId][$categoryId];
    }

    /** The attribute's value at $storeId, else at the default scope; '' when neither exists. */
    private function valueAtStore($connection, string $table, int $entityTypeId, string $code, int $entityId, int $storeId): string
    {
        $select = $connection->select()
            ->from(['v' => $connection->getTableName($table)], ['value'])
            ->join(['a' => $connection->getTableName('eav_attribute')], 'a.attribute_id = v.attribute_id', [])
            ->where('a.attribute_code = ?', $code)
            ->where('a.entity_type_id = ?', $entityTypeId)
            ->where('v.entity_id = ?', $entityId)
            ->where('v.store_id IN (?)', [0, $storeId])
            ->order('v.store_id DESC')
            ->limit(1);

        return trim((string) $connection->fetchOne($select));
    }
}
