<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HeroBanner\Model\Banner;
use MagentoEgypt\HeroBanner\Model\ResourceModel\Banner\CollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * The whole homepage hero band: carousel slides plus the side tiles.
 *
 * ONE block, one query, one template. The two halves have to agree on height and
 * on how many tiles fit beside the carousel, and when they were separate blocks —
 * a category-driven carousel and a hand-written CMS block — nothing enforced
 * that. They now come out of the same collection.
 */
class Band extends Template
{
    /** @var array<string, array<int, array<string, mixed>>>|null */
    private ?array $rows = null;

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSlides(): array
    {
        return $this->load()[Banner::SLOT_HERO] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTiles(): array
    {
        $tiles = $this->load()[Banner::SLOT_TILE] ?? [];
        $limit = (int) ($this->getData('tile_limit') ?: 3);

        return array_slice($tiles, 0, $limit);
    }

    public function hasContent(): bool
    {
        return $this->getSlides() !== [] || $this->getTiles() !== [];
    }

    /**
     * Absolute media URL for a stored path, or null when the row has no image.
     */
    public function getImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        try {
            $base = $this->storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        } catch (\Throwable $e) {
            $this->logger->warning('HeroBanner: media base URL unavailable: ' . $e->getMessage());
            return null;
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Destination for a row. Relative paths are resolved against the store's base
     * URL rather than through the URL builder — these point at category and CMS
     * pages, not at routes, and getUrl() would prepend routing segments to them.
     */
    public function getLinkUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $this->getBaseUrl();
        }
        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        return rtrim($this->getBaseUrl(), '/') . '/' . ltrim($url, '/');
    }

    /**
     * Fetch both slots in one query, grouped.
     *
     * Store resolution: rows scoped to the current store view win over the
     * all-stores rows (store_id 0). Selecting both and preferring the specific
     * one in PHP costs one query; doing it in SQL would need a correlated
     * subquery per slot for no measurable gain on a table this size.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function load(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $this->rows = [Banner::SLOT_HERO => [], Banner::SLOT_TILE => []];

        try {
            $storeId = (int) $this->storeManager->getStore()->getId();

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('store_id', ['in' => [0, $storeId]])
                ->setOrder('sort_order', 'ASC')
                ->setOrder('banner_id', 'ASC');

            $bySlot = [];
            foreach ($collection as $banner) {
                $slot = (string) $banner->getData('slot');
                if (!isset($this->rows[$slot])) {
                    continue;
                }
                $bySlot[$slot][] = $banner->getData();
            }

            foreach ($bySlot as $slot => $items) {
                $this->rows[$slot] = $this->preferStoreScope($items, $storeId);
            }
        } catch (\Throwable $e) {
            /*
             * A missing table (module enabled before setup:upgrade ran) must not
             * take the homepage down — the band simply does not render.
             */
            $this->logger->warning('HeroBanner: band unavailable: ' . $e->getMessage());
            $this->rows = [Banner::SLOT_HERO => [], Banner::SLOT_TILE => []];
        }

        return $this->rows;
    }

    /**
     * Drop the all-stores row at a position when a store-specific one exists.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function preferStoreScope(array $items, int $storeId): array
    {
        $specific = [];
        foreach ($items as $item) {
            if ((int) $item['store_id'] === $storeId) {
                $specific[(int) $item['sort_order']] = true;
            }
        }

        return array_values(array_filter(
            $items,
            static fn (array $item): bool =>
                (int) $item['store_id'] === $storeId
                || !isset($specific[(int) $item['sort_order']])
        ));
    }
}
