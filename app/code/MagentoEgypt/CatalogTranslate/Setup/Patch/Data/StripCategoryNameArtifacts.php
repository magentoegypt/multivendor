<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\CatalogTranslate\Setup\Patch\Data;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Data-entry artifacts in the English category names.
 *
 * THE "m" SUFFIX
 * --------------
 * Seven men's-branch categories carry a raw "m"/"men" marker that shoppers see:
 * "Jackets m", "Pants m", "Top clothes men". It exists because the admin's
 * product-edit category picker is a flat, path-less list fed by the store-0 name,
 * so whoever built the tree needed to tell two "Jackets" apart in that box.
 *
 * WHY STRIP IT RATHER THAN EXPAND IT TO "Men's Jackets"
 * -----------------------------------------------------
 * The case for expanding rests on the label having to stand alone somewhere. On
 * this storefront it never does. Measured on the live site, not assumed — I had
 * assumed the opposite and was wrong:
 *
 *   <title>     Jackets m - Top clothes men - mens wear - clothes
 *   hero trail  Home / clothes / mens wear / Top clothes men / Jackets m
 *   breadcrumb  Home / clothes / mens wear / Top clothes men / Jackets m
 *   mega-menu   mobile only, indented under its parent
 *
 * The title is not the bare name: Catalog\Block\Category\View::_prepareLayout
 * uses meta_title only when it is set, and it is unset on every one of these
 * nodes, so the fallback joins the reversed breadcrumb path. And the h1 is not
 * bare either — this theme's category/hero.phtml renders the whole ancestor
 * chain in a <nav class="hm-cat-hero__trail"> directly above the heading.
 *
 * So all four surfaces show the chain, the gender is already carried at level 3,
 * and the label can drop it. That is the convention the Arabic store has shipped
 * all along — 145 and 153 are both جاكيتات, 143 and 151 are both ملابس علوية —
 * so stripping makes the two stores agree instead of diverging.
 *
 * The seven new English repeats this creates are all nested under different
 * parents, which is precisely the case FixDuplicateCategoryLabels classes as
 * acceptable. No amendment to that rule is needed.
 *
 * WHY 142 IS IN, WHEN NOBODY ASKED
 * --------------------------------
 * "mens wear" is lowercase and missing its apostrophe next to a properly written
 * "Women's clothing". Cosmetic today. But once the leaves below it are stripped,
 * it becomes the ONLY token separating seven pairs of otherwise identical pages,
 * and it prints in seven titles and seven breadcrumb trails. The strip promotes
 * it from wart to load-bearing, so leaving it would be a new inconsistency
 * created by this change rather than one inherited from before it.
 *
 * Sentence case — "Men's clothing", not "Men's Clothing" — to match 141 exactly.
 * Title-casing it would oblige 141 to be recased too, which turns an eight-label
 * change into a ten-label one for no gain.
 *
 * The women's branch is NOT touched. It has no defect.
 *
 * WHITESPACE: HYGIENE, NOT A VISIBLE BUG
 * --------------------------------------
 * Five names carry a leading or trailing space (" Slipper", "apple ",
 * " Home Appliances", "Refrigerators ", "Lighting "). Measured: the anchors
 * compute white-space: normal, so the browser collapses it and shoppers see
 * nothing wrong. These are corrected because they are the same data-entry root
 * cause and cost nothing, NOT because anything renders badly. Worth being clear
 * about, so nobody later goes looking for the visual defect that fixed them.
 *
 * URLs ARE DELIBERATELY UNCHANGED
 * -------------------------------
 * url_key is a separate attribute and Magento only derives it from the name on
 * creation, so renaming here leaves /…/jackets-m.html and /…/top-clothes-men/
 * exactly as they are. That is intentional: rewriting the keys would need
 * permanent redirects in the same release, and a stable URL behind a clean label
 * is a defensible state. If the marker must disappear from the address bar too,
 * that is a separate, SEO-owned change.
 */
class StripCategoryNameArtifacts implements DataPatchInterface
{
    /**
     * All at store 0. Every one of these categories has its own store-1 Arabic
     * value, so changing the default cannot leak into the Arabic storefront —
     * checked before writing. Store 3 carries no override for any of them, which
     * is exactly why the store-0 default is what English shoppers see.
     *
     * id => [expected current value, new value]
     */
    private const RENAME = [
        // The reported defect: the "m" / "men" marker.
        151 => ['Top clothes men',         'Top clothes'],
        152 => ['Hoodies & Sweatshirts m', 'Hoodies & Sweatshirts'],
        153 => ['Jackets m',               'Jackets'],
        154 => ['T-shirts m',              'T-shirts'],
        155 => ['Underwear m',             'Underwear'],
        156 => ['Pants m',                 'Pants'],
        157 => ['Shorts m',                'Shorts'],

        // Made load-bearing by the strip above.
        142 => ['mens wear',               "Men's clothing"],
    ];

    /**
     * Leading/trailing whitespace. Invisible to shoppers; corrected as hygiene.
     *
     * The store the bad value lives in is NOT the same for all of these — four
     * are store-0 defaults but 111 "Lighting " is a store-3 English override,
     * and trimming only store 0 would have silently skipped it. So the trim
     * walks every store row for these ids rather than assuming a scope.
     *
     * @var int[]
     */
    private const TRIM = [59, 70, 95, 97, 111];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavConfig $eavConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getDependencies(): array
    {
        return [FixDuplicateCategoryLabels::class];
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

            $table = $this->moduleDataSetup->getTable('catalog_category_entity_varchar');

            foreach (self::RENAME as $categoryId => [$expected, $new]) {
                $current = $connection->fetchOne(
                    $connection->select()
                        ->from($table, 'value')
                        ->where('entity_id = ?', $categoryId)
                        ->where('attribute_id = ?', $nameId)
                        ->where('store_id = ?', 0)
                );

                /*
                 * Ids are not portable and these are ordinary editable names, so
                 * a row is only rewritten while it still holds the exact string
                 * this change was written against.
                 */
                if ($current === false || (string) $current !== $expected) {
                    $this->logger->info(sprintf(
                        'CatalogTranslate: category %d not renamed — expected "%s", found "%s".',
                        $categoryId,
                        $expected,
                        $current === false ? '(no row)' : (string) $current
                    ));
                    continue;
                }

                $connection->update(
                    $table,
                    ['value' => $new],
                    [
                        'entity_id = ?'    => $categoryId,
                        'attribute_id = ?' => $nameId,
                        'store_id = ?'     => 0,
                    ]
                );
            }

            foreach (self::TRIM as $categoryId) {
                $rows = $connection->fetchPairs(
                    $connection->select()
                        ->from($table, ['store_id', 'value'])
                        ->where('entity_id = ?', $categoryId)
                        ->where('attribute_id = ?', $nameId)
                );

                foreach ($rows as $storeId => $value) {
                    $trimmed = trim((string) $value);
                    if ($trimmed === '' || $trimmed === (string) $value) {
                        continue;
                    }

                    $connection->update(
                        $table,
                        ['value' => $trimmed],
                        [
                            'entity_id = ?'    => $categoryId,
                            'attribute_id = ?' => $nameId,
                            'store_id = ?'     => (int) $storeId,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('CatalogTranslate: ' . $e->getMessage());
        }

        $connection->endSetup();

        return $this;
    }
}
