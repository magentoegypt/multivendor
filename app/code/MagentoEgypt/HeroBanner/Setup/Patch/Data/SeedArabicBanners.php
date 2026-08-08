<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Arabic copy for the hero band.
 *
 * WHY ROWS AND NOT i18n. The band's text used to come from layout arguments, so
 * __() and i18n/ar_SA.csv translated it. Now it comes from the database, and
 * running merchandiser-authored content through the translation layer is the
 * wrong mechanism twice over: the CSV is a developer artefact that a
 * merchandiser cannot edit, and a headline typed in admin would never have a key
 * to match. Verified on the live Arabic homepage before this patch — the
 * headline translated (an old CSV entry happened to match) while the subtext,
 * the CTA and two of the three tile titles stayed in English, which is a worse
 * result than either language alone.
 *
 * The store_id column exists for exactly this. Rows here carry the Arabic store
 * view's id and the same sort_order as their English counterparts, so
 * Block\Band::preferStoreScope() serves them in place of the store_id 0 rows.
 *
 * Idempotent, and skipped entirely if no Arabic store view exists.
 */
class SeedArabicBanners implements DataPatchInterface
{
    private const STORE_CODE = 'ar';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @return array<int, string>
     */
    public static function getDependencies(): array
    {
        return [SeedFigmaBanners::class];
    }

    /**
     * @return array<int, string>
     */
    public function getAliases(): array
    {
        return [];
    }

    public function apply(): self
    {
        try {
            $storeId = (int) $this->storeManager->getStore(self::STORE_CODE)->getId();
        } catch (\Throwable $e) {
            return $this;   // no Arabic store view on this environment
        }

        $connection = $this->moduleDataSetup->getConnection();
        $table      = $this->moduleDataSetup->getTable('magentoegypt_hero_banner');

        $existing = (int) $connection->fetchOne(
            $connection->select()->from($table, 'COUNT(*)')->where('store_id = ?', $storeId)
        );
        if ($existing > 0) {
            return $this;
        }

        $rows = [
            [
                'slot' => 'hero', 'sort_order' => 10, 'is_active' => 1, 'store_id' => $storeId,
                'kicker' => 'توصيل في نفس اليوم',
                'title' => 'بقالة طازجة من بائعين محليين',
                'subtitle' => 'خضروات وفواكه طازجة ومنتجات ألبان وأساسيات المطبخ، تصلك خلال ساعات.',
                'cta_label' => 'تسوق البقالة',
                'url' => 'super-market.html',
                'image' => 'hero/hero-grocery.jpg',
            ],
            [
                'slot' => 'hero', 'sort_order' => 20, 'is_active' => 1, 'store_id' => $storeId,
                'kicker' => 'موسم جديد',
                'title' => 'أزياء من بائعين مصريين موثوقين',
                'subtitle' => 'ملابس يومية ومحتشمة وماركات عالمية في سوق واحد.',
                'cta_label' => 'تسوق الأزياء',
                'url' => 'clothes.html',
                'image' => 'hero/hero-fashion.jpg',
            ],
            [
                'slot' => 'hero', 'sort_order' => 30, 'is_active' => 1, 'store_id' => $storeId,
                'kicker' => 'منزل مميز',
                'title' => 'أثاث وديكور لكل منزل',
                'subtitle' => 'كنب وأسرّة وإضاءة من صنّاع محليين، مع التوصيل لجميع المحافظات.',
                'cta_label' => 'تسوق الأثاث',
                'url' => 'furniture.html',
                'image' => 'hero/hero-furniture.jpg',
            ],
            [
                'slot' => 'tile', 'sort_order' => 10, 'is_active' => 1, 'store_id' => $storeId,
                'kicker' => null, 'cta_label' => null,
                'title' => 'عروض الإلكترونيات',
                'subtitle' => 'موبايلات وتابلت وإكسسوارات',
                'url' => 'mobile-tablet.html',
                'image' => 'hero/tile-electronics.jpg',
            ],
            [
                'slot' => 'tile', 'sort_order' => 20, 'is_active' => 1, 'store_id' => $storeId,
                'kicker' => null, 'cta_label' => null,
                'title' => 'الجمال والعطور',
                'subtitle' => 'وصل حديثًا كل يوم',
                'url' => 'health.html',
                'image' => 'hero/tile-beauty.jpg',
            ],
            [
                'slot' => 'tile', 'sort_order' => 30, 'is_active' => 1, 'store_id' => $storeId,
                'kicker' => null, 'cta_label' => null,
                'title' => 'الأطفال والألعاب',
                'subtitle' => 'آمنة وتعليمية',
                'url' => 'toys.html',
                'image' => 'hero/tile-kids.jpg',
            ],
        ];

        /* insertMultiple needs one identical column set across every row. */
        $columns = array_keys($rows[0]);
        $rows = array_map(
            static function (array $row) use ($columns): array {
                $normalised = [];
                foreach ($columns as $column) {
                    $normalised[$column] = $row[$column] ?? null;
                }

                return $normalised;
            },
            $rows
        );

        $connection->insertMultiple($table, $rows);

        return $this;
    }
}
