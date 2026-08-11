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
 * The English category labels that were the wrong words, not the wrong casing.
 *
 * The casing pass deliberately shipped every label with its existing wording and
 * listed the bad ones for a separate decision. This is that decision.
 *
 * TWO KINDS OF DEFECT
 * -------------------
 * NOT ENGLISH, OR NAMING THE WRONG THING. Most of these are literal renderings
 * of an idiomatic Arabic label, where the Arabic is right and the English is a
 * word-for-word carry-over:
 *   89  External Hards     "hards" is not a noun. ar هاردات خارجية is external
 *                          hard drives.
 *   88  Electronic Flash   reads as a camera strobe. ar فلاشة is the Egyptian
 *                          word for a USB stick. → Flash Drives.
 *   133 Back Bag           ar حقيبة ظهر is a backpack, and the men's sibling
 *                          already reads "Men's Backpack".
 *   131 Hand Bag           closed compound in English; sibling "Men's Handbag".
 *   134 Soiree Bag         English retail says evening bag.
 *   135 Suitcase           ar حقيبة سفر is luggage generally. → Travel Bag.
 *   62  Classic            ar رسمي is formal/dress. → Formal Shoes.
 *   144 Underwear          ar ملابس سفلية is LOWER GARMENTS, and the children
 *   155                    are Pants and Shorts. Underwear is ملابس داخلية —
 *                          a different thing, and no such category exists here.
 *                          → Bottoms.
 *   143 Top Clothes        ar ملابس علوية is upper garments. → Tops.
 *   151
 *
 * COUNTABLE SINGULAR AMONG PLURAL SIBLINGS. Flat, Boot, Slipper, Laptop,
 * Headphone, TV and Women's Bag all sat singular next to plural siblings.
 * Pluralised. Mass nouns were left alone — Sports Equipment, Sportswear,
 * Storage Media, Personal Care, Lighting, Sand, Wood and the rest are correctly
 * singular and are not in this patch.
 *
 * WHY THE SHOE LABELS ARE DECIDED FROM THE ARABIC AND NOT THE PRODUCTS
 * --------------------------------------------------------------------
 * The obvious check on "Classic → Formal Shoes" is whether the category holds
 * formal shoes. It does not — it holds a Chelsea boot, white sneakers and a
 * slide. But that is not evidence against the label, because the whole branch is
 * like that: 56 "Flat", 58 "Boot" and 59 "Slipper" all contain THE SAME product
 * (one velvet-trimmed flat shoe in six colours), and 63 holds the same mixture
 * as 62. The product assignments in Shoes are noise, so they cannot validate or
 * refute any label here. The Arabic is the only deliberate signal, and رسمي is
 * unambiguously formal/dress. Same reasoning holds سليبر → "Slippers": it is a
 * transliteration of the English word, so the faithful rendering is the English
 * word. The mis-categorisation itself is raised separately — it is a catalog
 * data problem, not a naming one, and renaming cannot fix it.
 *
 * WRITE SCOPE IS NOT UNIFORM
 * --------------------------
 * Every row here is a store-0 default EXCEPT 100 "Headphone", which is a store-3
 * English override. Writing that one at store 0 would have left the English
 * storefront still reading "Headphone" through its override and silently
 * replaced the ARABIC fallback instead — the exact inverse of the intent. Each
 * row therefore carries its own scope rather than sharing one loop.
 *
 * (100's store-0 value is هيدفون, so Arabic shoppers already see Arabic there
 * and nothing about this change reaches them.)
 *
 * URLs are untouched, as in every patch in this module: url_key is a separate
 * attribute, so /underwear.html, /back-bag.html, /electronic-flash.html and the
 * rest keep working. Rewriting them needs permanent redirects and is an SEO
 * decision.
 */
class FixCategoryWording implements DataPatchInterface
{
    /**
     * [category id, store id, expected current value, new value]
     *
     * Pairs that must stay byte-identical because they are the same shelf on the
     * two gender branches: 58/61 Boots, 59/63 Slippers, 143/151 Tops,
     * 144/155 Bottoms.
     */
    private const WORDING = [
        // --- Shoes. Decided from the Arabic; see the class docblock. ---
        [56,  0, 'Flat',                       'Flats'],
        [58,  0, 'Boot',                       'Boots'],
        [61,  0, 'Boot',                       'Boots'],
        [59,  0, 'Slipper',                    'Slippers'],
        [63,  0, 'Slipper',                    'Slippers'],
        [62,  0, 'Classic',                    'Formal Shoes'],
        [93,  0, 'Sport Shoes',                'Sports Shoes'],

        // --- Computing ---
        [78,  0, 'Laptop',                     'Laptops'],
        //  Store 3, not store 0 — this label is an English override.
        [100, 3, 'Headphone',                  'Headphones'],
        //  Punctuation only. "Inks" is kept: ar أحبار is the countable plural of
        //  حبر, so the English plural is what matches the other store view.
        [87,  0, 'Inks and Printing Supplies', 'Inks & Printing Supplies'],
        [88,  0, 'Electronic Flash',           'Flash Drives'],
        [89,  0, 'External Hards',             'External Hard Drives'],

        // --- Appliances ---
        [99,  0, 'TV',                         'TVs'],

        // --- Bags. Leaves stay singular: the untouched men's leaves
        //     (Men's Handbag, Men's Crossbody Bag, Men's Backpack) are singular,
        //     so pluralising only the women's side would create a new asymmetry.
        [130, 0, "Women's Bag",                "Women's Bags"],
        [131, 0, 'Hand Bag',                   'Handbag'],
        [133, 0, 'Back Bag',                   'Backpack'],
        [134, 0, 'Soiree Bag',                 'Evening Bag'],
        [135, 0, 'Suitcase',                   'Travel Bag'],

        // --- Clothing ---
        [143, 0, 'Top Clothes',                'Tops'],
        [151, 0, 'Top Clothes',                'Tops'],
        [144, 0, 'Underwear',                  'Bottoms'],
        [155, 0, 'Underwear',                  'Bottoms'],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavConfig $eavConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getDependencies(): array
    {
        //  The expected values below are the POST-CASING strings, so this must
        //  run after the casing pass or every row would miss its guard.
        return [TitleCaseCategoryNames::class];
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

            $table   = $this->moduleDataSetup->getTable('catalog_category_entity_varchar');
            $written = 0;
            $skipped = 0;

            foreach (self::WORDING as [$categoryId, $storeId, $expected, $new]) {
                $current = $connection->fetchOne(
                    $connection->select()
                        ->from($table, 'value')
                        ->where('entity_id = ?', $categoryId)
                        ->where('attribute_id = ?', $nameId)
                        ->where('store_id = ?', $storeId)
                );

                if ($current !== false && (string) $current === $new) {
                    $skipped++;
                    continue;
                }

                if ($current === false || (string) $current !== $expected) {
                    $this->logger->info(sprintf(
                        'CatalogTranslate: category %d/store %d not reworded — expected "%s", found "%s".',
                        $categoryId,
                        $storeId,
                        $expected,
                        $current === false ? '(no row)' : (string) $current
                    ));
                    $skipped++;
                    continue;
                }

                $connection->update(
                    $table,
                    ['value' => $new],
                    [
                        'entity_id = ?'    => $categoryId,
                        'attribute_id = ?' => $nameId,
                        'store_id = ?'     => $storeId,
                    ]
                );
                $written++;
            }

            $this->logger->info(
                sprintf('CatalogTranslate: %d category labels reworded, %d left alone.', $written, $skipped)
            );
        } catch (\Throwable $e) {
            $this->logger->error('CatalogTranslate: ' . $e->getMessage());
        }

        $connection->endSetup();

        return $this;
    }
}
