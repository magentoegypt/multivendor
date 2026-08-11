<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\CatalogTranslate\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * The Shoes leaves were interchangeable. This makes them mean something.
 *
 * THE DEFECT, WHICH IS NOT "RANDOM"
 * ---------------------------------
 * Every product under Shoes was assigned to EVERY leaf of its gender branch.
 * A shopper clicking Men > Boots got the Chelsea boot AND the white sneakers
 * AND the black slides; clicking Men > Sneakers got the same three. Four
 * categories, one result set, zero information. The women's branch was the
 * same, and a second group (2209-2250) was assigned to BOTH Men and Women at
 * once, so a men's running shoe sat under Women and a women's flat under Men.
 *
 * WHAT I CHECKED BEFORE TOUCHING ANYTHING, AND WHAT IT CHANGED
 * ------------------------------------------------------------
 * Two things I had assumed were defects turned out not to be, and acting on
 * either would have made the catalog worse:
 *
 *   Configurable CHILDREN in categories. 1898 of the 1977 visibility=1 products
 *   in this catalog are assigned to categories. That is the house pattern, not
 *   a Shoes fault — stripping it here alone would have made this branch the
 *   odd one out for no shopper-visible gain, since Magento filters
 *   not-individually-visible products out of listings anyway. Children are
 *   therefore KEPT, and simply follow their parent to the correct leaf.
 *
 *   Explicit parent rows. Every category in the tree has is_anchor=1, so a leaf
 *   assignment already surfaces the product in Shoes and in its gender node.
 *   The explicit 53/54 rows are redundant — but they are also the existing
 *   convention here, and removing them would be churn with no effect. Kept.
 *
 * HOW EACH PRODUCT WAS PLACED
 * ---------------------------
 * By its photograph, not its name — the names are Arabic free text and two of
 * them actively mislead. Each product image was inspected:
 *
 *   2082  tan leather Chelsea boots, side gusset      -> Men > Boots
 *   2083  white lace-up court sneakers                -> Men > Sneakers
 *   2084  black rubber slides                         -> Men > Slippers
 *   2154  brown leather slip-on loafer                -> Men > Formal Shoes
 *   2217  black knit running shoes (+3 children)      -> Men > Sneakers
 *   2224  brown leather backless mule sandals (+2)    -> Men > Slippers
 *   2091  black ruched flat BACKLESS mules (+6)       -> Women > Slippers
 *   2092  purple pointed slingbacks, mid heel, bow    -> Women > High Heels
 *   2093  pink knit sock boots, block heel            -> Women > Boots
 *   2213  black chain-trim loafers (+4 children)      -> Women > Flats
 *   2218  cream leather ankle boots                   -> Women > Boots
 *   2221  white flatform strappy sandals (+2)         -> Women > Flats
 *
 * 2218 is the one the photo actually decided rather than confirmed: it was
 * assigned to BOTH genders and its Arabic name says nothing about who it is
 * for. The picture is a woman's manicured hand holding a pair of cream ankle
 * boots. Women's Boots.
 *
 * 2154 is a loafer, not a dress shoe, so "Formal Shoes" is the closest of four
 * imperfect options rather than a precise fit. There is no Loafers category and
 * inventing one is a tree change.
 *
 * 2091 IS THE ONE JUDGEMENT CALL, and it is worth being explicit about because
 * it also happens to be convenient. Its own name calls it a flat shoe, which
 * argues for Flats; the photograph shows BACKLESS mules, and this catalog's
 * سليبر is demonstrably the backless shelf — the men's side files black rubber
 * slides (2084) and backless leather mules (2224) there. Category placement is
 * a question about how this catalog uses its shelves, so the men's precedent
 * wins over the free-text name, and 2091 goes to Slippers.
 *
 * The convenient part: without it, Women > Slippers would come out of this
 * patch with zero products. That is NOT why the call was made — the men's
 * parallel stands on its own and 2091 would go to Slippers even if the shelf
 * were already full. But the coincidence is the kind of thing that quietly
 * bends a decision, so it is recorded rather than left unsaid. If the merchant
 * reads these as flats, moving them back leaves Women > Slippers empty and that
 * shelf then wants stocking or hiding.
 *
 * LEFT AT GENDER LEVEL ON PURPOSE, NOT OVERLOOKED
 * -----------------------------------------------
 *   2249 "black natural leather shoe"  image = no_selection
 *   2250 "white natural leather shoe"  image = no_selection
 * No photograph and a name that could be a sneaker or a dress shoe. Guessing
 * would put them in a leaf a shopper can be actively misled by, so they stay on
 * Men with no leaf. That is honest and still strictly better than being in all
 * four at once. They need a human who can see the stock.
 *
 * 2312 "test new bundle" is disabled test data sitting directly in Shoes; its
 * assignment is removed. The product itself is not touched.
 *
 * SCOPE. The patch only ever writes rows whose category_id is in the Shoes
 * tree. A product's assignments anywhere else in the catalog are not read and
 * not modified.
 */
class FixShoesCategoryAssignments implements DataPatchInterface
{
    /** The Shoes tree. Nothing outside this list is touched. */
    private const TREE = [53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63];

    private const SHOES  = 53;
    private const MEN    = 54;
    private const WOMEN  = 55;
    private const FLATS  = 56;
    private const HEELS  = 57;
    private const BOOTS_W = 58;
    private const SLIP_W = 59;   // no product lands here; see the docblock
    private const SNEAK  = 60;
    private const BOOTS_M = 61;
    private const FORMAL = 62;
    private const SLIP_M = 63;

    /**
     * product id => the exact set of Shoes-tree categories it should belong to.
     * An empty array means "remove from the Shoes tree entirely".
     *
     * Configurable children carry the same set as their parent, matching the
     * catalog-wide convention.
     */
    private const TARGET = [
        // ---- Men ----
        2082 => [self::SHOES, self::MEN, self::BOOTS_M],
        2083 => [self::SHOES, self::MEN, self::SNEAK],
        2084 => [self::SHOES, self::MEN, self::SLIP_M],
        2154 => [self::SHOES, self::MEN, self::FORMAL],

        2217 => [self::SHOES, self::MEN, self::SNEAK],   // configurable
        2214 => [self::SHOES, self::MEN, self::SNEAK],
        2215 => [self::SHOES, self::MEN, self::SNEAK],
        2216 => [self::SHOES, self::MEN, self::SNEAK],

        2224 => [self::SHOES, self::MEN, self::SLIP_M],  // configurable
        2222 => [self::SHOES, self::MEN, self::SLIP_M],
        2223 => [self::SHOES, self::MEN, self::SLIP_M],

        // No photograph, name is not decisive — gender only, no leaf.
        2249 => [self::SHOES, self::MEN],
        2250 => [self::SHOES, self::MEN],

        // ---- Women ----
        //  Backless flat mules. Slippers, not Flats — see the docblock note.
        2091 => [self::SHOES, self::WOMEN, self::SLIP_W], // configurable
        2085 => [self::SHOES, self::WOMEN, self::SLIP_W],
        2086 => [self::SHOES, self::WOMEN, self::SLIP_W],
        2087 => [self::SHOES, self::WOMEN, self::SLIP_W],
        2088 => [self::SHOES, self::WOMEN, self::SLIP_W],
        2089 => [self::SHOES, self::WOMEN, self::SLIP_W],
        2090 => [self::SHOES, self::WOMEN, self::SLIP_W],

        2092 => [self::SHOES, self::WOMEN, self::HEELS],
        2093 => [self::SHOES, self::WOMEN, self::BOOTS_W],
        2218 => [self::SHOES, self::WOMEN, self::BOOTS_W],

        2213 => [self::SHOES, self::WOMEN, self::FLATS], // configurable
        2209 => [self::SHOES, self::WOMEN, self::FLATS],
        2210 => [self::SHOES, self::WOMEN, self::FLATS],
        2211 => [self::SHOES, self::WOMEN, self::FLATS],
        2212 => [self::SHOES, self::WOMEN, self::FLATS],

        2221 => [self::SHOES, self::WOMEN, self::FLATS], // configurable
        2219 => [self::SHOES, self::WOMEN, self::FLATS],
        2220 => [self::SHOES, self::WOMEN, self::FLATS],

        // ---- Disabled test data ----
        2312 => [],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getDependencies(): array
    {
        return [FixCategoryWording::class];
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
            $table = $this->moduleDataSetup->getTable('catalog_category_product');
            $added = 0;
            $removed = 0;

            foreach (self::TARGET as $productId => $want) {
                $have = $connection->fetchCol(
                    $connection->select()
                        ->from($table, 'category_id')
                        ->where('product_id = ?', $productId)
                        ->where('category_id IN (?)', self::TREE)
                );
                $have = array_map('intval', $have);

                /*
                 * If a product no longer exists, or was never in this tree at
                 * all, leave it alone rather than creating assignments for it.
                 * This patch corrects placements, it does not merchandise.
                 */
                if (!$have) {
                    $this->logger->info(
                        sprintf('CatalogTranslate: product %d not in the Shoes tree; skipped.', $productId)
                    );
                    continue;
                }

                foreach (array_diff($have, $want) as $categoryId) {
                    $connection->delete($table, [
                        'product_id = ?'  => $productId,
                        'category_id = ?' => $categoryId,
                    ]);
                    $removed++;
                }

                foreach (array_diff($want, $have) as $categoryId) {
                    $connection->insertOnDuplicate(
                        $table,
                        ['category_id' => $categoryId, 'product_id' => $productId, 'position' => 0],
                        ['position']
                    );
                    $added++;
                }
            }

            $this->logger->info(sprintf(
                'CatalogTranslate: Shoes assignments — %d rows removed, %d added.',
                $removed,
                $added
            ));
        } catch (\Throwable $e) {
            $this->logger->error('CatalogTranslate: ' . $e->getMessage());
        }

        $connection->endSetup();

        return $this;
    }
}
