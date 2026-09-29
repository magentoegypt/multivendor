<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEgypt\HubApp\Model\ResourceModel\Section as SectionResource;
use MagentoEgypt\HubApp\Model\Source\ProductSort;
use MagentoEgypt\HubApp\Model\Source\SectionType as T;

/**
 * Seeds the app Home with the website's Home, section for section.
 *
 * The order is the `after=` chain of the theme's Magento_Cms/layout/
 * cms_index_index.xml (hero band -> chips -> deals -> picked for you ->
 * featured stores -> four category rails -> bundles -> promos -> best sellers
 * -> popular -> brands -> top vendors -> new stores -> trust), with the
 * delivery promise strip of the header first. Titles, subtitles, limits,
 * categories, seller codes, "view all" targets, chip order, glyphs and tints
 * are copied from that layout; the Arabic titles from the theme's ar_SA.csv.
 * Positions step by 10 so new sections fit in between.
 *
 * `hm_home_app` (the app-download promo) is deliberately not seeded: the app
 * does not advertise itself.
 *
 * Inserts only into an EMPTY table, so it never duplicates or overwrites what
 * a merchandiser has since built; to reseed, empty the table and remove this
 * patch's row from patch_list.
 */
class SeedHomeSections implements DataPatchInterface
{
    public function __construct(private readonly ModuleDataSetupInterface $moduleDataSetup)
    {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable(SectionResource::TABLE);

        if (!$connection->isTableExists($table)) {
            return $this;
        }
        $count = (int) $connection->fetchOne($connection->select()->from($table, ['COUNT(*)']));
        if ($count > 0) {
            return $this;
        }

        $connection->insertMultiple($table, array_map([$this, 'row'], self::sections()));

        return $this;
    }

    /**
     * The seeded Home, in order.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sections(): array
    {
        return [
            ['position' => 5, 'type' => T::DELIVERY_STRIP, 'cms_identifier' => 'hm_delivery_promise'],
            ['position' => 10, 'type' => T::HERO_BANNERS, 'item_limit' => 8, 'options' => ['tile_limit' => 3]],
            [
                'position' => 20,
                'type' => T::CATEGORY_CHIPS,
                'item_limit' => 8,
                'options' => [
                    //  The reference row. pharmacy and fmcg do not exist yet and are
                    //  skipped until they do, exactly as on the website.
                    'order' => ['super-market', 'pharmacy', 'furniture', 'clothes', 'fmcg', 'games', 'health', 'electronics'],
                    'icons' => [
                        'super-market' => "\u{1F6D2}",
                        'pharmacy' => "\u{1F48A}",
                        'furniture' => "\u{1F6CB}\u{FE0F}",
                        'clothes' => "\u{1F457}",
                        'fmcg' => "\u{1F4E6}",
                        'games' => "\u{1F9F8}",
                        'health' => "\u{2728}",
                        'electronics' => "\u{1F4F1}",
                        'home-appliances' => "\u{1F3E0}",
                        'mobile-tablet' => "\u{1F4F1}",
                        'computer' => "\u{1F4BB}",
                        'bags' => "\u{1F45C}",
                        'shoes' => "\u{1F45F}",
                        'accessories' => "\u{1F457}",
                        'sports' => "\u{26BD}",
                    ],
                    'tints' => [
                        'super-market' => 0,
                        'pharmacy' => 1,
                        'furniture' => 2,
                        'clothes' => 3,
                        'fmcg' => 4,
                        'games' => 5,
                        'health' => 6,
                        'electronics' => 7,
                        'home-appliances' => 6,
                        'bags' => 7,
                        'shoes' => 5,
                        'mobile-tablet' => 1,
                        'computer' => 4,
                        'sports' => 0,
                    ],
                ],
            ],
            [
                'position' => 30,
                'type' => T::TODAYS_DEALS,
                'title_en' => "Today's Deals",
                'title_ar' => 'عروض اليوم',
                'item_limit' => 4,
                'more_url' => 'all.html',
            ],
            [
                'position' => 40,
                'type' => T::PICKED_FOR_YOU,
                'title_en' => 'Picked For You',
                'title_ar' => 'مختارة لك',
                'subtitle_en' => 'Personalised recommendations based on your search history & behaviour',
                'subtitle_ar' => 'توصيات مخصّصة بناءً على سجل بحثك وسلوكك',
                'item_limit' => 4,
                'more_url' => 'all.html',
            ],
            [
                'position' => 50,
                'type' => T::FEATURED_STORES,
                'title_en' => 'Featured Stores',
                'title_ar' => 'متاجر مختارة',
                'vendor_codes' => 'ENARA,ronza,loly,MIA',
                'item_limit' => 4,
                'more_url' => 'sellerlist',
            ],
            [
                'position' => 60,
                'type' => T::CATEGORY_RAIL,
                'title_en' => 'Grocery Essentials',
                'title_ar' => 'أساسيات البقالة',
                'subtitle_en' => 'Fresh & delivered today',
                'subtitle_ar' => 'طازج ويصلك اليوم',
                'category_id' => 101,
                'item_limit' => 4,
                'sort_by' => ProductSort::NEWEST,
                'more_url' => 'super-market.html',
            ],
            [
                'position' => 70,
                'type' => T::CATEGORY_RAIL,
                'title_en' => 'Fashion Trends',
                'title_ar' => 'أحدث صيحات الموضة',
                'subtitle_en' => 'New arrivals this week',
                'subtitle_ar' => 'وصل حديثًا هذا الأسبوع',
                'category_id' => 140,
                'item_limit' => 4,
                'sort_by' => ProductSort::NEWEST,
                'more_url' => 'clothes.html',
            ],
            [
                'position' => 80,
                'type' => T::CATEGORY_RAIL,
                'title_en' => 'Beauty & Cosmetics',
                'title_ar' => 'الجمال ومستحضرات التجميل',
                'subtitle_en' => 'Curated beauty picks',
                'subtitle_ar' => 'مختارات الجمال',
                'category_id' => 103,
                'item_limit' => 4,
                'sort_by' => ProductSort::NEWEST,
                'more_url' => 'health.html',
            ],
            [
                'position' => 90,
                'type' => T::CATEGORY_RAIL,
                'title_en' => 'Furniture Picks',
                'title_ar' => 'مختارات الأثاث',
                'subtitle_en' => 'For your home',
                'subtitle_ar' => 'لمنزلك',
                'category_id' => 74,
                'item_limit' => 4,
                'sort_by' => ProductSort::NEWEST,
                'more_url' => 'furniture.html',
            ],
            [
                'position' => 100,
                'type' => T::BUNDLE_DEALS,
                'title_en' => 'Bundle Deals',
                'title_ar' => 'عروض الباقات',
                'subtitle_en' => 'Buy together, save more — curated multi-item bundles',
                'subtitle_ar' => 'اشترِ معًا ووفّر أكثر — باقات مختارة متعددة المنتجات',
                'item_limit' => 4,
                'more_url' => 'bundles',
            ],
            ['position' => 110, 'type' => T::CMS_PROMOS, 'cms_identifier' => 'hm_home_promos'],
            [
                'position' => 120,
                'type' => T::BEST_SELLERS,
                'title_en' => 'Best Selling Items',
                'title_ar' => 'الأكثر مبيعًا',
                'subtitle_en' => 'Top-rated across all categories this month',
                'subtitle_ar' => 'الأعلى تقييمًا في كل الأقسام هذا الشهر',
                'item_limit' => 6,
                'more_url' => 'all.html',
            ],
            [
                'position' => 130,
                'type' => T::POPULAR_PRODUCTS,
                'title_en' => 'Popular Products',
                'title_ar' => 'منتجات رائجة',
                'item_limit' => 8,
                'sort_by' => ProductSort::NEWEST,
                'more_url' => 'all.html',
            ],
            [
                'position' => 140,
                'type' => T::TOP_BRANDS,
                'title_en' => 'Top Brands on Hub Market',
                'title_ar' => 'أشهر العلامات التجارية على Hub Market',
                'item_limit' => 8,
                'more_url' => 'brand',
            ],
            [
                'position' => 150,
                'type' => T::TOP_VENDORS,
                'title_en' => 'Top Vendors This Month',
                'title_ar' => 'أفضل البائعين هذا الشهر',
                'subtitle_en' => 'Highest-rated sellers verified by Hub Market',
                'subtitle_ar' => 'أعلى البائعين تقييمًا والموثّقون من Hub Market',
                'item_limit' => 4,
                'sort_by' => ProductSort::TOP_RATED,
                'more_url' => 'sellerlist',
            ],
            [
                'position' => 160,
                'type' => T::NEW_STORES,
                'title_en' => 'New Stores on Hub Market',
                'title_ar' => 'متاجر جديدة على Hub Market',
                'subtitle_en' => 'Recently joined verified sellers',
                'subtitle_ar' => 'بائعون موثّقون انضموا حديثًا',
                'item_limit' => 4,
                'sort_by' => ProductSort::NEWEST,
                'more_url' => 'sellerlist',
            ],
            ['position' => 170, 'type' => T::TRUST_ROW, 'cms_identifier' => 'hm_home_trust'],
        ];
    }

    /**
     * @param array<string, mixed> $section
     * @return array<string, mixed>
     */
    private function row(array $section): array
    {
        $options = $section['options'] ?? null;

        return [
            'type' => $section['type'],
            'title_en' => $section['title_en'] ?? null,
            'title_ar' => $section['title_ar'] ?? null,
            'subtitle_en' => $section['subtitle_en'] ?? null,
            'subtitle_ar' => $section['subtitle_ar'] ?? null,
            'category_id' => $section['category_id'] ?? null,
            'vendor_codes' => $section['vendor_codes'] ?? null,
            'product_skus' => null,
            'cms_identifier' => $section['cms_identifier'] ?? null,
            'item_limit' => $section['item_limit'] ?? 8,
            'sort_by' => $section['sort_by'] ?? null,
            'more_url' => $section['more_url'] ?? null,
            //  Escaped unicode on purpose: the glyphs are 4-byte emoji and the
            //  column may be utf8 (3-byte). json_decode restores them.
            'options' => $options ? json_encode($options) : null,
            'starts_at' => null,
            'ends_at' => null,
            'audience' => 'all',
            'store_id' => 0,
            'position' => $section['position'],
            'is_active' => 1,
        ];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
