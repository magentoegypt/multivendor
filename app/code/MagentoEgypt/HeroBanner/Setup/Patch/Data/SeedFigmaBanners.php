<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Setup\Patch\Data;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Seeds the hero band with the reference design's own banners.
 *
 * IMAGES: the three carousel photographs and the three tile photographs are the
 * ones the Figma build uses, downloaded to pub/media/hero rather than hotlinked.
 * They are Unsplash stock under the Unsplash License (free commercial use, no
 * attribution required) — placeholders that look like the design, not final
 * merchandising. Replacing them is the whole reason this module has an admin
 * grid: Content > Hero Banner > edit > Image.
 *
 * COPY is the reference's, adapted where the reference makes a claim this store
 * cannot: its slides advertise Gulf delivery and "500+ Gulf Brands", which is
 * neither true here nor translatable into anything true. Headlines that describe
 * the merchandise are kept verbatim.
 *
 * LINKS point at categories that exist on this catalog, checked against the
 * category tree: super-market, clothes, furniture, mobile-tablet, health, toys.
 *
 * Idempotent: it does nothing if the table already has rows, so re-running
 * setup:upgrade never duplicates the band or overwrites edited copy.
 */
class SeedFigmaBanners implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array<int, string>
     */
    public static function getDependencies(): array
    {
        return [];
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
        $connection = $this->moduleDataSetup->getConnection();
        $table      = $this->moduleDataSetup->getTable('magentoegypt_hero_banner');

        $this->installImages();

        if ((int) $connection->fetchOne("SELECT COUNT(*) FROM {$table}") > 0) {
            return $this;
        }

        $rows = [
            // --- carousel slides -------------------------------------------------
            [
                'slot' => 'hero', 'sort_order' => 10, 'is_active' => 1, 'store_id' => 0,
                'kicker' => 'Same-Day Delivery',
                'title' => 'Fresh Groceries From Local Vendors',
                'subtitle' => 'Organic produce, dairy and pantry essentials, delivered in hours.',
                'cta_label' => 'Shop Grocery',
                'url' => 'super-market.html',
                'image' => 'hero/hero-grocery.jpg',
            ],
            [
                'slot' => 'hero', 'sort_order' => 20, 'is_active' => 1, 'store_id' => 0,
                'kicker' => 'New Season',
                'title' => 'Fashion From Verified Egyptian Sellers',
                'subtitle' => 'Everyday wear, modest wear and international labels in one marketplace.',
                'cta_label' => 'Shop Fashion',
                'url' => 'clothes.html',
                'image' => 'hero/hero-fashion.jpg',
            ],
            [
                'slot' => 'hero', 'sort_order' => 30, 'is_active' => 1, 'store_id' => 0,
                'kicker' => 'Premium Home',
                'title' => 'Furniture and Decor for Every Home',
                'subtitle' => 'Sofas, beds and lighting from local makers, delivered nationwide.',
                'cta_label' => 'Shop Furniture',
                'url' => 'furniture.html',
                'image' => 'hero/hero-furniture.jpg',
            ],

            // --- side tiles ------------------------------------------------------
            [
                'slot' => 'tile', 'sort_order' => 10, 'is_active' => 1, 'store_id' => 0,
                'kicker' => null, 'cta_label' => null,
                'title' => 'Electronics Deals',
                'subtitle' => 'Phones, tablets and accessories',
                'url' => 'mobile-tablet.html',
                'image' => 'hero/tile-electronics.jpg',
            ],
            [
                'slot' => 'tile', 'sort_order' => 20, 'is_active' => 1, 'store_id' => 0,
                'kicker' => null, 'cta_label' => null,
                'title' => 'Beauty and Perfumes',
                'subtitle' => 'New arrivals daily',
                'url' => 'health.html',
                'image' => 'hero/tile-beauty.jpg',
            ],
            [
                'slot' => 'tile', 'sort_order' => 30, 'is_active' => 1, 'store_id' => 0,
                'kicker' => null, 'cta_label' => null,
                'title' => 'Kids and Toys',
                'subtitle' => 'Safe and educational',
                'url' => 'toys.html',
                'image' => 'hero/tile-kids.jpg',
            ],
        ];

        /*
         * insertMultiple builds ONE multi-row INSERT, so every row must carry the
         * identical column set in the identical order — a tile row that simply
         * omitted `kicker` and `cta_label` failed with "Invalid data for insert".
         * Normalising here rather than padding each literal keeps the rows above
         * readable as content.
         */
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

    /**
     * Copy the shipped banner images into pub/media/hero.
     *
     * They live in the module because `pub/media` is gitignored on this project:
     * an image referenced only from pub/media would exist on the machine it was
     * downloaded to and nowhere else, so a fresh checkout would seed six rows
     * pointing at six 404s.
     *
     * Runs BEFORE the "already seeded" check on purpose — a database that already
     * has banners still needs the files present. Never overwrites: once a
     * merchandiser has replaced a banner the module has no business restoring it.
     */
    private function installImages(): void
    {
        $source = __DIR__ . '/../../../media';
        if (!is_dir($source)) {
            return;
        }

        try {
            $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $media->create('hero');

            foreach (glob($source . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $file) {
                $target = 'hero/' . basename($file);
                if ($media->isExist($target)) {
                    continue;
                }
                $media->writeFile($target, (string) file_get_contents($file));
            }
        } catch (\Throwable $e) {
            /*
             * A read-only or misconfigured media directory must not abort
             * setup:upgrade — the rows are still valid and the images can be
             * uploaded from admin.
             */
            $this->logger->warning('HeroBanner: could not install banner images: ' . $e->getMessage());
        }
    }
}
