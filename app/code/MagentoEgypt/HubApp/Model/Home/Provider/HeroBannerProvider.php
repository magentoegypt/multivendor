<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home\Provider;

use MagentoEgypt\HeroBanner\Model\BandReader;
use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;
use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;

/**
 * HERO_BANNERS: the hero band of Content > Elements > Hero Banner, as the
 * website shows it (BandReader: store view rows override all-store rows at the
 * same position). Slides first, then tiles; the section's item limit caps the
 * slides and the `tile_limit` option the tiles (3 like the website when unset).
 *
 * Banner saves purge hm_app_home (Observer\CleanHomeOnHeroBannerChange).
 */
class HeroBannerProvider implements SectionProviderInterface
{
    public const DEFAULT_TILE_LIMIT = 3;

    public function __construct(
        private readonly BandReader $bandReader,
        private readonly MediaUrlInterface $mediaUrl,
        private readonly LinkResolverInterface $links
    ) {
    }

    public function provide(SectionContext $context): ?SectionResult
    {
        $storeId = $context->getStoreId();
        $tileLimit = $context->getOption('tile_limit');
        $band = $this->bandReader->read(
            $storeId,
            $tileLimit === null || $tileLimit === '' ? self::DEFAULT_TILE_LIMIT : max(0, (int) $tileLimit),
            $context->getLimit()
        );

        $rows = [];
        foreach ($band['slides'] as $row) {
            $rows[] = ['slot' => 'SLIDE', 'row' => $row];
        }
        foreach ($band['tiles'] as $row) {
            $rows[] = ['slot' => 'TILE', 'row' => $row];
        }
        if (!$rows) {
            return null;
        }

        $targets = [];
        foreach ($rows as $i => $entry) {
            //  An empty destination is the store's home page, as on the website.
            $url = trim((string) ($entry['row']['url'] ?? ''));
            $targets[$i] = $url !== '' ? $url : '/';
        }
        $links = $this->links->resolveMany($targets, $storeId);

        $banners = [];
        foreach ($rows as $i => $entry) {
            $row = $entry['row'];
            $title = trim((string) ($row['title'] ?? ''));
            $link = $links[$i] ?? null;
            if ($title === '' || $link === null) {
                continue;
            }
            $banners[] = [
                'id' => (int) $row['banner_id'],
                'slot' => $entry['slot'],
                'title' => $title,
                'kicker' => self::text($row['kicker'] ?? null),
                'subtitle' => self::text($row['subtitle'] ?? null),
                'cta_label' => self::text($row['cta_label'] ?? null),
                'image_url' => $this->mediaUrl->media((string) ($row['image'] ?? ''), $storeId),
                'tone' => self::hex($row['tone'] ?? null),
                'accent' => self::hex($row['accent'] ?? null),
                'link' => $link,
            ];
        }

        return $banners ? SectionResult::create()->withField('banners', $banners) : null;
    }

    private static function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /**
     * "#abc" / "abc" / "#aabbcc" -> "#aabbcc"; anything else null (the theme default).
     */
    public static function hex(mixed $value): ?string
    {
        $hex = strtolower(ltrim(trim((string) $value), '#'));
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return preg_match('/^[0-9a-f]{6}$/', $hex) ? '#' . $hex : null;
    }
}
