<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home\Provider;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;
use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;
use MagentoEgypt\HubApp\Model\Product\RankedLists;
use MagentoEgypt\HubApp\Model\Source\ProductSort;
use MagentoEgypt\HubApp\Model\Source\SectionType;

/**
 * The product rails of the Home. Each returns ranked, gated ids; the products
 * field loads every rail of the Home in one collection.
 *
 *   PICKED_FOR_YOU    top rated (>= 2 approved reviews); the app may replace it
 *                     with the customer's own recently viewed (personalizable)
 *   CATEGORY_RAIL     the category, children included, NEWEST unless sorted;
 *                     title and "view all" default to the category's own
 *   BEST_SELLERS      units ordered, cancelled orders excluded
 *   POPULAR_PRODUCTS  the catalogue (or a category), NEWEST unless sorted — the
 *                     website's "Popular Products" widget default
 *   PRODUCT_LIST      hand-picked SKUs in admin order
 */
class ProductRailProvider implements SectionProviderInterface
{
    public function __construct(
        private readonly RankedLists $lists,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly LinkResolverInterface $links
    ) {
    }

    public function provide(SectionContext $context): ?SectionResult
    {
        $storeId = $context->getStoreId();
        $limit = $context->getLimit();
        $result = SectionResult::create();

        switch ($context->getType()) {
            case SectionType::PICKED_FOR_YOU:
                $ids = $this->lists->topRated($storeId, $limit);
                $result = $result->withTags([Tags::APP_CATALOG]);
                break;
            case SectionType::BEST_SELLERS:
                $ids = array_slice($this->lists->bestSellers($storeId), 0, $limit);
                $result = $result->withTags([Tags::APP_CATALOG]);
                break;
            case SectionType::CATEGORY_RAIL:
                $categoryId = $context->getCategoryId();
                if ($categoryId === null) {
                    return null;
                }
                $ids = $this->lists->catalog($storeId, $categoryId, $this->sort($context), $limit);
                $category = $this->category($categoryId, $storeId);
                if ($category !== null) {
                    $result = $result->withDefaultTitle($category['name'])
                        ->withDefaultMoreLink($this->links->category($categoryId, $category['request_path'], $storeId))
                        ->withTags([Tags::category($categoryId)]);
                }
                $result = $result->withTags([Tags::APP_CATALOG]);
                break;
            case SectionType::POPULAR_PRODUCTS:
                $ids = $this->lists->catalog($storeId, $context->getCategoryId(), $this->sort($context), $limit);
                $result = $result->withTags([Tags::APP_CATALOG]);
                break;
            case SectionType::PRODUCT_LIST:
                $ids = $this->lists->skus($context->getProductSkus(), $storeId, $limit);
                break;
            default:
                return null;
        }

        return $ids ? $result->withProductIds($ids) : null;
    }

    private function sort(SectionContext $context): string
    {
        $sort = $context->getSortBy();

        return $sort !== null && in_array($sort, ProductSort::PRODUCT_SORTS, true) ? $sort : ProductSort::NEWEST;
    }

    /**
     * Name and URL path of an active category in the store view, or null.
     *
     * @return array{name: string, request_path: string|null}|null
     */
    private function category(int $categoryId, int $storeId): ?array
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->setStoreId($storeId)
            ->addAttributeToSelect(['name'])
            ->addAttributeToFilter('is_active', 1)
            ->addIdFilter([$categoryId]);
        $collection->addUrlRewriteToResult();
        $category = $collection->getFirstItem();
        if (!$category->getId()) {
            return null;
        }
        $path = trim((string) $category->getData('request_path'));

        return [
            'name' => (string) $category->getName(),
            'request_path' => $path !== '' ? $path : null,
        ];
    }
}
