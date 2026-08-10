<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Backfills the per-slide scrim and accent on the seeded hero slides.
 *
 * A separate patch rather than an edit to SeedFigmaBanners: that one is already
 * applied on every environment and is guarded against re-running, so changing it
 * would ship the columns to no one.
 *
 * Values are the reference build's own, converted from the oklab it encodes them
 * in. Note the furniture accent: #c85c2c measures 4.18:1 against white and 3.8:1
 * against navy — it fails BOTH, so there is no readable label for it. It is
 * stored as configured and Block\Band substitutes an accessible colour at render
 * time, which keeps the merchandiser's intent visible in admin rather than
 * silently rewriting their value in the database.
 *
 * Matched on slot + sort_order, which is stable for the seeded rows and does not
 * depend on auto-increment ids that differ per environment. Only fills rows that
 * are still empty, so an edited tone is never overwritten.
 */
class AddSlideTones implements DataPatchInterface
{
    private const TONES = [
        10 => ['tone' => '#0d3320', 'accent' => '#2d7a3a'],   // grocery  — deep green
        20 => ['tone' => '#0f2144', 'accent' => '#f26522'],   // fashion  — navy
        30 => ['tone' => '#2a1200', 'accent' => '#c85c2c'],   // furniture — warm brown
    ];

    public function __construct(private readonly ModuleDataSetupInterface $moduleDataSetup)
    {
    }

    /**
     * @return array<int, string>
     */
    public static function getDependencies(): array
    {
        return [SeedFigmaBanners::class, SeedArabicBanners::class];
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

        foreach (self::TONES as $sortOrder => $colours) {
            $connection->update(
                $table,
                $colours,
                [
                    'slot = ?'       => 'hero',
                    'sort_order = ?' => $sortOrder,
                    '(tone IS NULL OR tone = ?)' => '',
                ]
            );
        }

        return $this;
    }
}
