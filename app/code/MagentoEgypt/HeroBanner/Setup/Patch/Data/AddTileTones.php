<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Backfills the per-tile scrim on the seeded side tiles.
 *
 * Same reasoning as AddSlideTones, for the other half of the band: Figma tints
 * each tile to its own photograph rather than using one house navy. Converted
 * from the oklab the reference encodes them in.
 *
 * No accent column here — a tile has no pill and no button, only a scrim and
 * white copy over it.
 *
 * Matched on slot + sort_order, and only fills rows still empty.
 */
class AddTileTones implements DataPatchInterface
{
    private const TONES = [
        10 => '#0f2144',   // electronics — navy
        20 => '#3a1a2e',   // beauty      — plum
        30 => '#1a2a0a',   // kids        — dark olive
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

        foreach (self::TONES as $sortOrder => $tone) {
            $connection->update(
                $table,
                ['tone' => $tone],
                [
                    'slot = ?'       => 'tile',
                    'sort_order = ?' => $sortOrder,
                    '(tone IS NULL OR tone = ?)' => '',
                ]
            );
        }

        return $this;
    }
}
