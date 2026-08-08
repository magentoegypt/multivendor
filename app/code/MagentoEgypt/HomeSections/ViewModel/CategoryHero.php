<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\ViewModel;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * The category page's navy hero band: trail, title, product count, thumbnail.
 *
 * Figma opens a listing with a full-bleed navy band carrying the breadcrumb, the
 * category name in the display serif, "N products from verified sellers", and a
 * small 96x64 thumbnail of the category. Magento scatters those four things
 * across three containers — breadcrumbs in page.top, the title in main, the
 * category image inside the category view block — and renders the image as a
 * ~300px full-width banner the design does not have at all.
 *
 * This gathers them so one template can lay them out as one band.
 */
class CategoryHero implements ArgumentInterface
{
    private ?Category $category = null;
    private bool $resolved = false;

    public function __construct(
        private readonly Registry $registry,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getCategory(): ?Category
    {
        if (!$this->resolved) {
            $this->resolved = true;
            $current = $this->registry->registry('current_category');
            $this->category = $current instanceof Category ? $current : null;
        }

        return $this->category;
    }

    public function getName(): string
    {
        $category = $this->getCategory();

        return $category ? (string) $category->getName() : '';
    }

    /**
     * Category image URL, or null. Never the placeholder: an empty band reads
     * better than a grey "no image" tile at 96x64.
     */
    public function getImageUrl(): ?string
    {
        $category = $this->getCategory();
        if (!$category) {
            return null;
        }

        try {
            $url = $category->getImageUrl();
        } catch (\Throwable $e) {
            $this->logger->warning('CategoryHero: image unavailable: ' . $e->getMessage());

            return null;
        }

        return $url ?: null;
    }

    /**
     * Products in this category.
     *
     * The CATEGORY's own count, not the layer's filtered one — Figma's line reads
     * "2,156 products from verified sellers", a statement about the category,
     * while the toolbar right beside it already reports the filtered "N results".
     * Showing the same number twice would waste the line.
     */
    public function getProductCount(): int
    {
        $category = $this->getCategory();
        if (!$category) {
            return 0;
        }

        try {
            return (int) $category->getProductCount();
        } catch (\Throwable $e) {
            $this->logger->warning('CategoryHero: product count unavailable: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Ancestor trail, root excluded, current category last.
     *
     * Built here rather than reused from Magento's breadcrumbs block because that
     * block renders into page.top and its markup is a <ul> this band cannot use;
     * duplicating the DATA is cheaper than moving the block and unpicking the
     * schema markup other pages depend on.
     *
     * @return array<int, array{name: string, url: ?string}>
     */
    public function getTrail(): array
    {
        $category = $this->getCategory();
        if (!$category) {
            return [];
        }

        $trail = [];

        try {
            $rootId = (int) $this->storeManager->getStore()->getRootCategoryId();
            $storeId = (int) $this->storeManager->getStore()->getId();

            foreach (explode('/', (string) $category->getPath()) as $id) {
                $id = (int) $id;
                if ($id <= $rootId) {
                    continue;   // skips both the tree root (1) and the store root
                }
                $node = $this->categoryRepository->get($id, $storeId);
                $trail[] = [
                    'name' => (string) $node->getName(),
                    'url'  => $id === (int) $category->getId() ? null : $node->getUrl(),
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('CategoryHero: trail unavailable: ' . $e->getMessage());

            return [];
        }

        return $trail;
    }
}
