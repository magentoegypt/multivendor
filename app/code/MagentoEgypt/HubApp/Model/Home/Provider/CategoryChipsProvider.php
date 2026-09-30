<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home\Provider;

use Magento\Framework\GraphQl\Query\Uid;
use MagentoEgypt\HomeSections\Model\CategoryChip\CategoryChipReader;
use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;
use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;

/**
 * CATEGORY_CHIPS: "Shop by category" with live counts, the website's rules
 * (CategoryChipReader) and the chip order, glyphs and tints of the section's
 * options (seeded from the theme layout). No glyph configured: null, and the
 * app draws its own.
 */
class CategoryChipsProvider implements SectionProviderInterface
{
    public function __construct(
        private readonly CategoryChipReader $reader,
        private readonly LinkResolverInterface $links,
        private readonly Uid $uid
    ) {
    }

    public function provide(SectionContext $context): ?SectionResult
    {
        $storeId = $context->getStoreId();
        $icons = [];
        foreach ((array) $context->getOption('icons', []) as $key => $glyph) {
            $icons[strtolower((string) $key)] = (string) $glyph;
        }
        $tints = [];
        foreach ((array) $context->getOption('tints', []) as $key => $slot) {
            $tints[strtolower((string) $key)] = (int) $slot;
        }

        $chips = $this->reader->getChips(
            $storeId,
            $context->getLimit(),
            array_map('strval', (array) $context->getOption('order', [])),
            $icons,
            $tints,
            null
        );
        if (!$chips) {
            return null;
        }

        $out = [];
        //  The generic tag is what core cleans from the app cache on a category save
        //  (AbstractModel::cleanModelCache); the per-id tags purge the HTTP cache.
        $tags = [Tags::CATEGORY];
        foreach ($chips as $chip) {
            $out[] = [
                'id' => $chip['id'],
                'uid' => $this->uid->encode((string) $chip['id']),
                'name' => $chip['name'],
                'url_key' => $chip['url_key'],
                'product_count' => $chip['count'],
                'icon' => $chip['icon'],
                'tint' => $chip['tint'],
                'link' => $this->links->category($chip['id'], $chip['request_path'], $storeId),
            ];
            $tags[] = Tags::category($chip['id']);
        }

        return SectionResult::create()->withField('categories', $out)->withTags($tags);
    }
}
