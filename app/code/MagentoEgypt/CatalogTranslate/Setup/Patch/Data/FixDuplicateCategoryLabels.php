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
 * Categories that render the SAME label twice in one menu.
 *
 * MOST "DUPLICATES" ARE NOT DUPLICATES.
 * -------------------------------------
 * Grouping active categories by rendered label finds 10 repeated labels on the
 * English store and 15 on Arabic — and acting on that list would wreck the menu.
 * The overwhelming majority are siblings under DIFFERENT parents, where the parent
 * supplies the distinction and the repetition is the design:
 *
 *   بوت        under أحذية ← نساء   and أحذية ← رجال
 *   آبل        under موبايل         and تابلت
 *   نسائي      under حقائب          and ملابس     ← deliberate: the parent is the noun
 *   ملابس علوية under نسائي          and رجالي
 *
 * Arabic makes this look worse than it is because the house style drops the noun
 * from a child when the parent already carries it. نسائي appearing twice is that
 * convention working, not a fault.
 *
 * A repeat is only a defect when the two nodes sit where nothing distinguishes
 * them: at the SAME menu level, or as parent and child of each other. Three cases
 * survive that filter, and this patch fixes exactly those three.
 *
 * 1. A CHILD LABELLED AS ITS OWN PARENT (English store)
 *    103 الجمال والعطور has store-3 English "Beauty & Perfumes".
 *    119 مستحضرات تجميل has store-3 English "Beauty & Perfumes" TOO.
 *    So the English menu shows "Beauty & Perfumes" containing "Beauty & Perfumes".
 *    The Arabic is fine — the two defaults are distinct — which is why this was
 *    invisible until the English store was grouped separately. 119 is cosmetics;
 *    its siblings are already Beauty Tools / Perfumes / Personal Care, so the
 *    label that fits the set is "Cosmetics".
 *
 * 2. A LATIN AMPERSAND INSIDE AN ARABIC LABEL
 *    146 "هوديس& سويتشيرتات" and 152 "هوديس&سويتشيرتات". In an RTL run a bare "&"
 *    is neutral-direction, so it is reordered to the wrong side of the phrase and
 *    the space lands on the wrong side of the ampersand. Both become the Arabic
 *    conjunction و, which is what the catalog already does in موبايل وتابلت, and
 *    what the new top-level node was given.
 *
 * 3. STRAY TOP-LEVEL COPIES OF REAL SUB-CATEGORIES
 *    176 Jackets, 177 Hoodies & Sweatshirts and 204 women's bag were created at
 *    level 2 in June 2026 alongside proper sub-categories of the same name that
 *    sit four levels deep. Top level means no parent context, so the mega-menu
 *    renders جاكيتات as a column heading AND as a leaf under ملابس, identically.
 *
 *    They carry nothing. Measured, not assumed:
 *      176  1 product, already in 145
 *      177  1 product, already in 146
 *      204  2 products, 1 already in 130; the other is p135
 *           "Stark Fundamental Hoodie-XS-Purple" — a hoodie filed under women's
 *           bags, and visibility=1 (Not Visible Individually), so it is a
 *           configurable's child that is never shown on its own anyway.
 *
 *    WHAT THIS PATCH DOES NOT DO: it does not delete them, and it does not
 *    disable them. Both are the merchant's call and neither is reversible in one
 *    step — deleting drops the store-1 URL rewrites (/jackets.html,
 *    /hoodies-sweatshirts.html, /women-s-bag.html) and would 404 them. Clearing
 *    include_in_menu removes the ambiguous menu entry, which IS the measured
 *    defect, leaves every URL working, and is undone by flipping one flag.
 */
class FixDuplicateCategoryLabels implements DataPatchInterface
{
    /**
     * Store-scoped label corrections.
     *
     * [category id, store the value belongs to, expected current value, new value]
     * The expected value is the portability guard: on an install where the row
     * holds something else the correction is skipped rather than applied blind.
     */
    private const LABELS = [
        // Child rendering its parent's label on the English store.
        ['id' => 119, 'store' => 'en', 'from' => 'Beauty & Perfumes', 'to' => 'Cosmetics'],

        // Latin ampersand inside an RTL label.
        ['id' => 146, 'store' => 'ar', 'from' => 'هوديس& سويتشيرتات', 'to' => 'هوديس وسويتشيرتات'],
        ['id' => 152, 'store' => 'ar', 'from' => 'هوديس&سويتشيرتات',  'to' => 'هوديس وسويتشيرتات'],
    ];

    /**
     * Stray top-level copies to drop out of the menu.
     *
     * Guarded by the English name so an id that has been reused elsewhere is left
     * alone. Values are the store-0 default name.
     */
    private const DEMOTE = [
        176 => 'Jackets',
        177 => 'Hoodies & Sweatshirts',
        204 => "women's bag",
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
        //  Runs after the name backfill: that patch writes 177's Arabic label, and
        //  this one is reasoning about the label set as a whole.
        return [TranslateCategoryNames::class];
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
            $nameId = (int) $this->eavConfig
                ->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'name')
                ->getAttributeId();
            $menuId = (int) $this->eavConfig
                ->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'include_in_menu')
                ->getAttributeId();

            $varchar = $this->moduleDataSetup->getTable('catalog_category_entity_varchar');
            $int     = $this->moduleDataSetup->getTable('catalog_category_entity_int');

            $byLocale = $this->storeIdsByLanguage();

            foreach (self::LABELS as $row) {
                $storeIds = $byLocale[$row['store']] ?? [];
                if (!$storeIds) {
                    $this->logger->warning(
                        sprintf('CatalogTranslate: no "%s" store view; skipped category %d.', $row['store'], $row['id'])
                    );
                    continue;
                }

                foreach ($storeIds as $storeId) {
                    $current = $connection->fetchOne(
                        $connection->select()
                            ->from($varchar, 'value')
                            ->where('entity_id = ?', $row['id'])
                            ->where('attribute_id = ?', $nameId)
                            ->where('store_id = ?', $storeId)
                    );

                    if ($current === false || trim((string) $current) !== $row['from']) {
                        $this->logger->info(sprintf(
                            'CatalogTranslate: label %d/store %d left alone — expected "%s", found "%s".',
                            $row['id'],
                            $storeId,
                            $row['from'],
                            $current === false ? '(no row)' : (string) $current
                        ));
                        continue;
                    }

                    $connection->insertOnDuplicate(
                        $varchar,
                        [
                            'attribute_id' => $nameId,
                            'store_id'     => $storeId,
                            'entity_id'    => $row['id'],
                            'value'        => $row['to'],
                        ],
                        ['value']
                    );
                }
            }

            foreach (self::DEMOTE as $categoryId => $expectedName) {
                $default = $connection->fetchOne(
                    $connection->select()
                        ->from($varchar, 'value')
                        ->where('entity_id = ?', $categoryId)
                        ->where('attribute_id = ?', $nameId)
                        ->where('store_id = ?', 0)
                );

                if ($default === false || trim((string) $default) !== $expectedName) {
                    $this->logger->info(sprintf(
                        'CatalogTranslate: category %d not demoted — expected "%s", found "%s".',
                        $categoryId,
                        $expectedName,
                        $default === false ? '(no row)' : (string) $default
                    ));
                    continue;
                }

                $connection->insertOnDuplicate(
                    $int,
                    [
                        'attribute_id' => $menuId,
                        'store_id'     => 0,
                        'entity_id'    => $categoryId,
                        'value'        => 0,
                    ],
                    ['value']
                );

                /*
                 * Any store-scoped override would win over the default just set,
                 * so those rows have to go too or the demotion silently does
                 * nothing on the store that carries one.
                 */
                $connection->delete($int, [
                    'attribute_id = ?' => $menuId,
                    'entity_id = ?'    => $categoryId,
                    'store_id > ?'     => 0,
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('CatalogTranslate: ' . $e->getMessage());
        }

        $connection->endSetup();

        return $this;
    }

    /**
     * Store view ids grouped by language, read from general/locale/code.
     *
     * @return array<string, int[]>
     */
    private function storeIdsByLanguage(): array
    {
        $connection  = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');

        $default = (string) $connection->fetchOne(
            $connection->select()
                ->from($configTable, 'value')
                ->where('path = ?', 'general/locale/code')
                ->where('scope = ?', 'default')
        ) ?: 'en_US';

        $out = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();

            $locale = $connection->fetchOne(
                $connection->select()
                    ->from($configTable, 'value')
                    ->where('path = ?', 'general/locale/code')
                    ->where('scope = ?', 'stores')
                    ->where('scope_id = ?', $storeId)
            );

            $locale = is_string($locale) && $locale !== '' ? $locale : $default;
            $out[substr($locale, 0, 2)][] = $storeId;
        }

        return $out;
    }
}
