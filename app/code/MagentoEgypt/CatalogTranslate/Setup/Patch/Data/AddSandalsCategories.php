<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\CatalogTranslate\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * A shelf for the sandals, which had nowhere correct to go.
 *
 * The Shoes reassignment put every product in a leaf that matched it, except
 * three: there was no Sandals category, so a women's flatform sandal went to
 * Flats and a men's leather sandal to Slippers — both recorded at the time as
 * "the least wrong of four options rather than right". This adds the option.
 *
 * WHY TWO CATEGORIES AND NOT ONE
 * ------------------------------
 * Every other leaf under Shoes hangs off a gender node — Men has Sneakers,
 * Boots, Formal Shoes, Slippers; Women has Flats, High Heels, Boots, Slippers.
 * A single Sandals directly under Shoes would be the only leaf in the branch
 * that is not gendered, and a shopper filtering to Women would stop seeing
 * sandals at all. So: one per gender, matching the shape that is already there.
 *
 * Both take url_key "sandals". That is not a collision — this tree already
 * reuses keys across the gender branches, with "boot" on 58 and 61 and
 * "slipper" on 59 and 63, and the full paths differ
 * (shoes/men/sandals vs shoes/women/sandals).
 *
 * WHAT MOVES, AND WHAT DELIBERATELY DOES NOT
 * ------------------------------------------
 *   2221 white flatform strappy sandals   Flats      -> Women > Sandals
 *   2224 brown leather backless sandals   Slippers   -> Men   > Sandals
 * with their configurable children, which follow their parent as everywhere
 * else in this catalog.
 *
 * 2084 STAYS in Men > Slippers. It is black rubber slides, and a slide is
 * arguably a sandal — but this catalog's سليبر is the slide/backless shelf (it
 * is why the women's ruched mules were filed there), and the product's own name
 * is حذاء سلايد, not صندل. Moving it would empty Slippers and blur the very
 * distinction this patch exists to sharpen. The two shelves now read cleanly:
 * Slippers is what you slide into, Sandals is what straps or buckles on.
 *
 * The result is one visible product per shelf, which is thin — but that is the
 * catalog having fourteen distinct shoes, not a structural problem, and a
 * correct thin shelf beats a wrong full one.
 *
 * IDEMPOTENT. The patch looks for an existing child of each gender node with
 * url_key "sandals" before creating anything, so a re-run adopts what is there
 * instead of making a second copy.
 *
 * Categories are created through CategoryRepository rather than by writing
 * rows: the repository is what maintains path, level and children_count and
 * what triggers URL rewrite generation. Hand-inserting the entity would leave
 * the tree metadata inconsistent and the category unreachable by URL.
 */
class AddSandalsCategories implements DataPatchInterface
{
    private const MEN   = 54;
    private const WOMEN = 55;
    private const FLATS = 56;
    private const SLIP_M = 63;

    private const URL_KEY = 'sandals';
    private const NAME_EN = 'Sandals';
    private const NAME_AR = 'صنادل';

    /** Products to move in, keyed by the gender node the new shelf hangs from. */
    private const MOVE = [
        self::WOMEN => [
            'from'     => self::FLATS,
            'products' => [2221, 2219, 2220],   // configurable + children
        ],
        self::MEN => [
            'from'     => self::SLIP_M,
            'products' => [2224, 2222, 2223],
        ],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryFactory $categoryFactory,
        private readonly CollectionFactory $categoryCollectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getDependencies(): array
    {
        return [FixShoesCategoryAssignments::class];
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
            foreach (self::MOVE as $parentId => $spec) {
                $categoryId = $this->findExisting((int) $parentId) ?? $this->create((int) $parentId);

                if (!$categoryId) {
                    continue;
                }

                $this->assign($categoryId, (int) $spec['from'], $spec['products']);
            }
        } catch (\Throwable $e) {
            $this->logger->error('CatalogTranslate: ' . $e->getMessage());
        }

        $connection->endSetup();

        return $this;
    }

    /**
     * An existing "sandals" child of this parent, if a previous run made one.
     */
    private function findExisting(int $parentId): ?int
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('parent_id', $parentId)
            ->addAttributeToFilter('url_key', self::URL_KEY)
            ->setPageSize(1);

        $existing = $collection->getFirstItem();

        return $existing->getId() ? (int) $existing->getId() : null;
    }

    private function create(int $parentId): ?int
    {
        try {
            $parent = $this->categoryRepository->get($parentId);
        } catch (\Throwable $e) {
            $this->logger->warning(
                sprintf('CatalogTranslate: parent category %d missing; Sandals not created.', $parentId)
            );

            return null;
        }

        $category = $this->categoryFactory->create();
        $category->setName(self::NAME_EN)
            ->setUrlKey(self::URL_KEY)
            ->setParentId($parentId)
            ->setPath($parent->getPath())
            ->setIsActive(true)
            ->setIncludeInMenu(true)
            ->setIsAnchor(true)
            ->setDisplayMode('PRODUCTS')
            //  Last in the column, after the four shelves already there.
            ->setPosition(5)
            ->setStoreId(0)
            ->setAttributeSetId($category->getDefaultAttributeSetId());

        $category = $this->categoryRepository->save($category);
        $categoryId = (int) $category->getId();

        $this->repairPath($categoryId, (string) $parent->getPath());
        $this->writeArabicName($categoryId);

        $this->logger->info(sprintf(
            'CatalogTranslate: created Sandals category %d under %d.',
            $categoryId,
            $parentId
        ));

        return $categoryId;
    }

    /**
     * Make sure the new category's path ends in its own id.
     *
     * IT DID NOT, ON THIS INSTALL. Magento builds the path in two halves —
     * ResourceModel\Category::_beforeSave appends a trailing "/" for a new
     * object and _afterSave then appends the id and re-saves the row. On a
     * clean install that works. Here the row came out of the repository save
     * holding the PARENT's path with neither the slash nor the id
     * (1/2/53/55 instead of 1/2/53/55/218), which makes the category
     * unreachable in the tree and leaves the URL rewrite generator producing
     * rewrites for only some store views.
     *
     * Something in this install's plugin stack — it carries Vnecoms, MGS,
     * Amasty, Lof and Mageplaza modules that all touch category save — drops
     * one of those two halves. Rather than guess which, the path is asserted
     * after the save and corrected if wrong. On an install where core behaves,
     * this is a no-op.
     *
     * `level` is derived from the path, so it is rewritten with it.
     */
    private function repairPath(int $categoryId, string $parentPath): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('catalog_category_entity');

        $want = $parentPath . '/' . $categoryId;
        $have = (string) $connection->fetchOne(
            $connection->select()->from($table, 'path')->where('entity_id = ?', $categoryId)
        );

        if ($have === $want) {
            return;
        }

        $connection->update(
            $table,
            ['path' => $want, 'level' => substr_count($want, '/')],
            ['entity_id = ?' => $categoryId]
        );

        $this->logger->warning(sprintf(
            'CatalogTranslate: category %d saved with path "%s"; corrected to "%s".',
            $categoryId,
            $have,
            $want
        ));
    }

    /**
     * The Arabic store-view label.
     *
     * Written directly rather than through a second scoped repository save: the
     * only difference between the two store views is this one string, and a
     * full save per store would re-run the URL rewrite generator for no reason.
     * Same approach the other patches in this module use.
     */
    private function writeArabicName(int $categoryId): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('catalog_category_entity_varchar');
        $configTable = $this->moduleDataSetup->getTable('core_config_data');

        $nameId = (int) $connection->fetchOne(
            $connection->select()
                ->from($this->moduleDataSetup->getTable('eav_attribute'), 'attribute_id')
                ->where('attribute_code = ?', 'name')
                ->where('entity_type_id = ?', $this->categoryEntityTypeId())
        );

        if (!$nameId) {
            return;
        }

        //  Arabic store views resolved from the locale, never assumed to be
        //  store 1 — that assumption has already cost this project an outage.
        $storeIds = $connection->fetchCol(
            $connection->select()
                ->from($configTable, 'scope_id')
                ->where('path = ?', 'general/locale/code')
                ->where('scope = ?', 'stores')
                ->where('value LIKE ?', 'ar%')
        );

        foreach ($storeIds as $storeId) {
            $connection->insertOnDuplicate(
                $table,
                [
                    'attribute_id' => $nameId,
                    'store_id'     => (int) $storeId,
                    'entity_id'    => $categoryId,
                    'value'        => self::NAME_AR,
                ],
                ['value']
            );
        }
    }

    private function categoryEntityTypeId(): int
    {
        $connection = $this->moduleDataSetup->getConnection();

        return (int) $connection->fetchOne(
            $connection->select()
                ->from($this->moduleDataSetup->getTable('eav_entity_type'), 'entity_type_id')
                ->where('entity_type_code = ?', 'catalog_category')
        );
    }

    /**
     * Move the products onto the new shelf and off the one they were parked on.
     *
     * @param int[] $productIds
     */
    private function assign(int $categoryId, int $fromCategoryId, array $productIds): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('catalog_category_product');

        foreach ($productIds as $productId) {
            /*
             * Only move a product that is actually still parked where this
             * patch expects it. If someone has already re-merchandised it, that
             * decision wins over this one.
             */
            $parked = $connection->fetchOne(
                $connection->select()
                    ->from($table, 'product_id')
                    ->where('product_id = ?', $productId)
                    ->where('category_id = ?', $fromCategoryId)
            );

            if (!$parked) {
                $this->logger->info(sprintf(
                    'CatalogTranslate: product %d no longer in category %d; left alone.',
                    $productId,
                    $fromCategoryId
                ));
                continue;
            }

            $connection->insertOnDuplicate(
                $table,
                ['category_id' => $categoryId, 'product_id' => $productId, 'position' => 0],
                ['position']
            );

            $connection->delete($table, [
                'product_id = ?'  => $productId,
                'category_id = ?' => $fromCategoryId,
            ]);
        }
    }
}
