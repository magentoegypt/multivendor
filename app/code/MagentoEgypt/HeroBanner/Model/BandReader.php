<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Model;

use MagentoEgypt\HeroBanner\Model\ResourceModel\Banner\CollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * The hero band's rows for one store view — the logic of Block\Band::load()
 * and preferStoreScope(), which are private to the block, extracted so the
 * app (HubApp HERO_BANNERS) reads the band exactly as the website renders it.
 *
 *   - active rows of store 0 and the store view, one query, both slots;
 *   - per slot and position, the store view's row REPLACES the all-stores row
 *     (how the Arabic band overrides copy without duplicating the set);
 *   - slides in sort_order, then id; tiles likewise, cut to the tile limit
 *     (the website shows 3).
 *
 * Returns raw rows; image and link resolution belong to the caller. A missing
 * table (module enabled before setup:upgrade) is logged and gives an empty
 * band, as on the website.
 */
class BandReader
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{slides: array<int, array<string, mixed>>, tiles: array<int, array<string, mixed>>}
     */
    public function read(int $storeId, ?int $tileLimit = 3, ?int $slideLimit = null): array
    {
        $bySlot = [Banner::SLOT_HERO => [], Banner::SLOT_TILE => []];

        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('store_id', ['in' => [0, $storeId]])
                ->setOrder('sort_order', 'ASC')
                ->setOrder('banner_id', 'ASC');

            foreach ($collection as $banner) {
                $slot = (string) $banner->getData('slot');
                if (isset($bySlot[$slot])) {
                    $bySlot[$slot][] = $banner->getData();
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('HeroBanner: band unavailable: ' . $e->getMessage());

            return ['slides' => [], 'tiles' => []];
        }

        $slides = self::preferStoreScope($bySlot[Banner::SLOT_HERO], $storeId);
        $tiles = self::preferStoreScope($bySlot[Banner::SLOT_TILE], $storeId);

        if ($slideLimit !== null) {
            $slides = array_slice($slides, 0, max(0, $slideLimit));
        }
        if ($tileLimit !== null) {
            $tiles = array_slice($tiles, 0, max(0, $tileLimit));
        }

        return ['slides' => $slides, 'tiles' => $tiles];
    }

    /**
     * Drop the all-stores row at a position when the store view has its own.
     *
     * @param array<int, array<string, mixed>> $items one slot's rows, in display order
     * @return array<int, array<string, mixed>>
     */
    public static function preferStoreScope(array $items, int $storeId): array
    {
        $specific = [];
        foreach ($items as $item) {
            if ((int) $item['store_id'] === $storeId) {
                $specific[(int) $item['sort_order']] = true;
            }
        }

        return array_values(array_filter(
            $items,
            static fn (array $item): bool => (int) $item['store_id'] === $storeId
                || !isset($specific[(int) $item['sort_order']])
        ));
    }
}
