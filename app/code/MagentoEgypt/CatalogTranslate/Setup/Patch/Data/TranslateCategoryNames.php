<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\CatalogTranslate\Setup\Patch\Data;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Arabic store-view names for the categories that were still rendering in English.
 *
 * WHAT WAS ACTUALLY WRONG
 * -----------------------
 * Not "the Arabic store has no translations" — it mostly does. This catalog carries
 * THREE store-scoped values per category: store 0 (admin default), store 3 (en) and
 * store 1 (ar). Roughly a third of the tree is authored Arabic-first, with the
 * ENGLISH supplied as the store-3 override — سوبر ماركت has "Supermarket" on store 3,
 * not the other way round. Counting rows missing a store-1 value therefore reports 52
 * categories as untranslated when most of them are Arabic already.
 *
 * The real defect is narrower and was found by rendering both menus and classifying
 * every label by script: 14 labels render Latin on the Arabic storefront nav, and 28
 * categories in total resolve to a Latin label there once the non-menu ones are
 * counted. This patch covers the 24 of those that are ACTIVE.
 *
 * NOT COVERED, deliberately:
 *   126 "T"          a level-1 root with no products and a one-letter name. Junk that
 *                    wants deleting or renaming, and translating it would preserve it.
 *   206 "Mahmoud"    is_active = 0. A disabled seller category.
 *   29  "Promotions" is_active = 0.
 *   7   "Collections" is_active = 0.
 * All four are merchant decisions, not translation work.
 *
 * WHY A DIRECT ATTRIBUTE WRITE AND NOT A CategoryRepository SAVE
 * --------------------------------------------------------------
 * Saving a category re-runs the URL rewrite generator and marks the category/product
 * indexers. Nothing here touches url_key, so all of that would be side-effect for no
 * gain, on a live catalog, for 24 rows. A scoped write to the name attribute is the
 * whole of the intended change.
 *
 * IDEMPOTENT, AND IT WILL NOT CLOBBER THE MERCHANT
 * ------------------------------------------------
 * Each row is written only when the current Arabic value is absent, empty, or still
 * contains Latin characters. Once a human has typed Arabic into any of these, this
 * patch leaves it alone — so it is safe to re-run and safe to keep in the tree.
 *
 * Entity ids are install-specific, so each one is GUARDED by the English name it is
 * expected to carry at store 0. On an install where that id holds something else the
 * row is skipped and logged rather than mistranslating an unrelated category.
 */
class TranslateCategoryNames implements DataPatchInterface
{
    /**
     * entity_id => [expected store-0 English name, Arabic store-view name]
     *
     * Wordings were produced by independent translation and adversarial review, then
     * checked against the Arabic already in this catalog and in the theme's i18n
     * files. The non-obvious calls, recorded so they are not "corrected" later by
     * someone reading only the English:
     *
     *   3   Gear          → مستلزمات الرياضة والسفر, not مستلزمات رياضية. The node's
     *                       stock is duffle bags and backpacks and its children are
     *                       Fitness Equipment + Watches, so "sports supplies" both
     *                       excludes most of it and collides with the existing
     *                       رياضة ← معدات رياضية. The idafa matches this repo's own
     *                       shape for the word (VendorExtend ar_SA.csv:309,
     *                       "Outdoor and Camping Gear" = مستلزمات التخييم والرحلات).
     *   9   Training      → دورات تدريبية, not تدريب. Its only child is Video Download
     *                       and its products are yoga/fitness VIDEO courses. This is
     *                       instructional content, not gym training.
     *   32  Pants         → بناطيل. US sense: trousers, not underwear.
     *   37  Sale          → تخفيضات, which is already how the theme renders SALE
     *                       (Mgs/supro/i18n/ar_SA.csv:64), so the menu agrees with itself.
     *   168 All           → كل المنتجات. A bare كل denotes nothing standing alone.
     *   177 Hoodies…      → هوديس وسويتشيرتات with the Arabic و. The legacy sibling
     *                       (id 146) reads هوديس& سويتشيرتات — a Latin ampersand, which
     *                       lands on the wrong side of the phrase in RTL. The catalog's
     *                       own good precedent is موبايل وتابلت. See the note below.
     *   204 women's bag   → حقائب نسائية, carrying the head noun. The identically named
     *                       sub-category (id 130) is just نسائي because its parent
     *                       حقائب supplies it; this one is top-level and cannot inherit.
     *   207 Amira         → أميرة. A native Arabic given name, written in its own form
     *                       rather than translated ("princess") or re-transliterated.
     *   217 Accessories   → إكسسوارات with the hamza. Every category label in this
     *                       catalog keeps it (أحذية، إنارة، إلكترونيات). The theme CSV's
     *                       ملحقات (supro:79) means device add-ons and would mis-denote
     *                       a fashion node.
     *   34  Erin Recommends → اختيارات ايرين. Foreign proper names take a bare alif here
     *                       (ايسر، اتش بي); native ones keep the hamza, hence أميرة above.
     */
    private const NAMES = [
        // --- active merchant categories, all of them visible in the Arabic menu ---
        168 => ['All',                    'كل المنتجات'],
        176 => ['Jackets',                'جاكيتات'],
        177 => ['Hoodies & Sweatshirts',  'هوديس وسويتشيرتات'],
        204 => ["women's bag",            'حقائب نسائية'],
        207 => ['Amira',                  'أميرة'],
        210 => ['Kitchen tools',          'أدوات مطبخ'],
        214 => ['Electronics',            'إلكترونيات'],
        217 => ['Accessories',            'إكسسوارات'],

        // --- Luma sample-data tree, still active and reachable by URL ---
        3   => ['Gear',                       'مستلزمات الرياضة والسفر'],
        5   => ['Fitness Equipment',          'معدات لياقة بدنية'],
        6   => ['Watches',                    'ساعات'],
        9   => ['Training',                   'دورات تدريبية'],
        10  => ['Video Download',             'فيديوهات للتحميل'],
        37  => ['Sale',                       'تخفيضات'],
        30  => ['Women Sale',                 'تخفيضات نسائية'],
        31  => ['Men Sale',                   'تخفيضات رجالية'],
        32  => ['Pants',                      'بناطيل'],
        33  => ['Tees',                       'تيشيرتات'],
        34  => ['Erin Recommends',            'اختيارات ايرين'],
        35  => ['Performance Fabrics',        'أقمشة عالية الأداء'],
        36  => ['Eco Friendly',               'منتجات صديقة للبيئة'],
        39  => ['Performance Sportswear New', 'ملابس رياضية جديدة'],
        40  => ['Eco Collection New',         'تشكيلة صديقة للبيئة'],
        8   => ['New Luma Yoga Collection',   'تشكيلة لوما لليوجا'],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavConfig $eavConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        try {
            $attributeId = (int) $this->eavConfig
                ->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'name')
                ->getAttributeId();

            $storeIds = $this->arabicStoreIds();
            if (!$storeIds) {
                $this->logger->warning('CatalogTranslate: no Arabic store view found; nothing written.');
                $connection->endSetup();

                return $this;
            }

            $table   = $this->moduleDataSetup->getTable('catalog_category_entity_varchar');
            $written = 0;
            $skipped = 0;

            foreach (self::NAMES as $categoryId => [$expectedEnglish, $arabic]) {
                $default = $connection->fetchOne(
                    $connection->select()
                        ->from($table, 'value')
                        ->where('entity_id = ?', $categoryId)
                        ->where('attribute_id = ?', $attributeId)
                        ->where('store_id = ?', 0)
                );

                /*
                 * The id guard. Category ids are not portable, so a row is only
                 * touched when it still holds the English name this translation was
                 * written for.
                 */
                if ($default === false || trim((string) $default) !== $expectedEnglish) {
                    $this->logger->info(sprintf(
                        'CatalogTranslate: skipped category %d — expected "%s", found "%s".',
                        $categoryId,
                        $expectedEnglish,
                        $default === false ? '(no row)' : (string) $default
                    ));
                    $skipped++;
                    continue;
                }

                foreach ($storeIds as $storeId) {
                    $current = $connection->fetchOne(
                        $connection->select()
                            ->from($table, 'value')
                            ->where('entity_id = ?', $categoryId)
                            ->where('attribute_id = ?', $attributeId)
                            ->where('store_id = ?', $storeId)
                    );

                    /*
                     * Leave anything a human has already written in Arabic. Only an
                     * absent, empty or still-Latin value is replaced.
                     */
                    if ($current !== false
                        && trim((string) $current) !== ''
                        && !preg_match('/[A-Za-z]/', (string) $current)
                    ) {
                        $skipped++;
                        continue;
                    }

                    $connection->insertOnDuplicate(
                        $table,
                        [
                            'attribute_id' => $attributeId,
                            'store_id'     => $storeId,
                            'entity_id'    => $categoryId,
                            'value'        => $arabic,
                        ],
                        ['value']
                    );
                    $written++;
                }
            }

            $this->logger->info(
                sprintf('CatalogTranslate: %d category names written, %d left alone.', $written, $skipped)
            );
        } catch (\Throwable $e) {
            /*
             * A label backfill must never take setup:upgrade down with it — a failure
             * here would block every other patch in the queue.
             */
            $this->logger->error('CatalogTranslate: ' . $e->getMessage());
        }

        $connection->endSetup();

        return $this;
    }

    /**
     * Every store view running an Arabic locale.
     *
     * Resolved from general/locale/code rather than hardcoding store_id 1 or the code
     * 'ar'. This install's Arabic view is ar_SA (ar_EG is configured on no store view
     * at all), and that has already caused one outage by being assumed — so the patch
     * asks rather than assumes.
     *
     * @return int[]
     */
    private function arabicStoreIds(): array
    {
        $connection = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');

        $ids = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();

            $locale = $connection->fetchOne(
                $connection->select()
                    ->from($configTable, 'value')
                    ->where('path = ?', 'general/locale/code')
                    ->where('scope = ?', 'stores')
                    ->where('scope_id = ?', $storeId)
            );

            if (is_string($locale) && str_starts_with($locale, 'ar')) {
                $ids[] = $storeId;
            }
        }

        return $ids;
    }
}
