<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\Breadcrumbs\ViewModel;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Crumbs for the product page: Home / Category / Product.
 *
 * WHY THIS EXISTS AT ALL. Magento renders the product breadcrumb in JavaScript —
 * `Magento_Catalog::product/breadcrumbs.phtml` prints an empty div and hands a
 * config to the `breadcrumbs` widget. That widget only emits a category when
 * `catalog/seo/product_use_categories` is on, and that setting's real job is to
 * put the category path into product URLs, i.e. rewriting every product URL on
 * the site. So the category could not be added by configuration without an SEO
 * change nobody asked for.
 *
 * Rendering server-side instead also puts the crumbs in the initial HTML, where
 * a crawler can see them, rather than building them after hydration.
 *
 * WHICH CATEGORY. The product's top-level (level 2) categories, narrowed to ones
 * a shopper can actually reach — active and in the menu — then ordered by the
 * merchandiser's own position, so the crumb names the same category the nav bar
 * lists first. A product in both Fashion and Bags therefore reads consistently
 * in both places.
 */
class ProductCrumbs implements ArgumentInterface
{
    /** @var array<int, array<int, array{label:string,link:?string}>> */
    private array $cache = [];

    public function __construct(
        private readonly Registry $registry,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $url,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array<int, array{label:string,link:?string}>
     */
    public function getCrumbs(): array
    {
        $product = $this->registry->registry('current_product');
        if (!$product) {
            return [];
        }

        $productId = (int) $product->getId();
        if (isset($this->cache[$productId])) {
            return $this->cache[$productId];
        }

        $crumbs = [[
            'label' => (string) __('Home'),
            'link'  => $this->url->getUrl(''),
        ]];

        try {
            $category = $this->resolveCategory($product);
            if ($category !== null) {
                $crumbs[] = $category;
            }
        } catch (\Throwable $e) {
            // A breadcrumb is navigation, not content. Losing the category level
            // is survivable; losing the product page is not.
            $this->logger->warning('Breadcrumbs: ' . $e->getMessage());
        }

        // The product is always last and never a link — it is the current page.
        $crumbs[] = ['label' => (string) $product->getName(), 'link' => null];

        return $this->cache[$productId] = $crumbs;
    }

    /**
     * @return array{label:string,link:?string}|null
     */
    private function resolveCategory(\Magento\Catalog\Model\Product $product): ?array
    {
        $categoryIds = array_map('intval', (array) $product->getCategoryIds());
        if (!$categoryIds) {
            return null;
        }

        $store = $this->storeManager->getStore();

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'url_key'])
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('include_in_menu', 1)
            ->addFieldToFilter('level', 2)
            ->addFieldToFilter('parent_id', (int) $store->getRootCategoryId())
            ->setStoreId((int) $store->getId())
            ->addAttributeToSort('position', 'ASC')
            ->setPageSize(1);

        $category = $collection->getFirstItem();
        if (!$category->getId()) {
            return null;
        }

        return [
            'label' => (string) $category->getName(),
            'link'  => (string) $category->getUrl(),
        ];
    }
}
