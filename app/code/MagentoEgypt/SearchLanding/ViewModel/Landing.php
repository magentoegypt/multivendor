<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SearchLanding\ViewModel;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory as QueryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Data behind the /search landing page.
 *
 * Two of the reference's three dynamic sections are backed by real data here;
 * the third (Recent Searches) is per-visitor and lives in localStorage, so it has
 * no server side at all — see web/js/recent-searches.js.
 *
 * TRENDING SEARCHES come from `search_query`, not from a hard-coded list. The
 * reference shows "Wireless headphones / Leather jacket / Yoga mat", none of
 * which this catalog sells; printing those would be a lie that also never
 * changes. The real table has genuine traffic in it ("bag" at 88, "iPhone 17",
 * "حقيبة") mixed with development noise ("test_2", "test new bundle").
 *
 * The noise is filtered the way Magento intends rather than by a blocklist in
 * PHP: `display_in_terms` is the flag behind Marketing > SEO & Search > Search
 * Terms, so a merchandiser hides a junk term by unticking it in admin and this
 * page follows without a deploy. A length floor is applied on top because
 * single-letter queries ("a", 82 hits on this install) are noise no admin should
 * have to curate one by one.
 *
 * POPULAR CATEGORIES follow the rule HeroCarousel already set on this install:
 * a category needs both an image and products to be shown. Sending a shopper to
 * an empty listing, or drawing a card with a hole where the photo goes, are both
 * worse than showing the next eligible category instead. That is why the limit is
 * applied AFTER filtering — the grid stays full at six rather than gapping when
 * one category happens to be ineligible.
 */
class Landing implements ArgumentInterface
{
    /**
     * Queries shorter than this are treated as noise regardless of popularity.
     * "a" is the top-scoring query on the Arabic store here with 82 hits.
     */
    private const MIN_QUERY_LENGTH = 3;

    /** @var array<int, array{text:string,url:string,count:int}>|null */
    private ?array $trending = null;

    /** @var array<int, array{id:int,name:string,url:string,image:string,count:int}>|null */
    private ?array $categories = null;

    public function __construct(
        private readonly QueryCollectionFactory $queryCollectionFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $url,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Where the hero field posts. The same endpoint the header search uses, so a
     * search started here is indistinguishable from one started anywhere else —
     * including for the recent-searches recorder, which reads ?q= off the result
     * page rather than hooking any particular form.
     */
    public function getSearchActionUrl(): string
    {
        return $this->url->getUrl('catalogsearch/result');
    }

    /**
     * The live-search endpoint. Built here rather than in JS because it is
     * store-scoped — /en/ and /ar/ are different URLs and the JS has no way to
     * know which one it is running under.
     */
    public function getLiveSearchUrl(): string
    {
        return $this->url->getUrl('search/ajax');
    }

    /**
     * @return array<int, array{text:string,url:string,count:int}>
     */
    public function getTrendingSearches(int $limit = 8): array
    {
        if ($this->trending !== null) {
            return $this->trending;
        }

        try {
            $storeId = (int) $this->storeManager->getStore()->getId();

            $collection = $this->queryCollectionFactory->create();
            $collection->addFieldToFilter('store_id', $storeId)
                ->addFieldToFilter('display_in_terms', 1)
                ->addFieldToFilter('num_results', ['gt' => 0])
                ->addFieldToFilter('popularity', ['gt' => 0])
                ->setOrder('popularity', 'DESC')
                // Over-fetch: the length floor below removes rows after the query,
                // so paging to $limit here could return fewer than $limit pills.
                ->setPageSize($limit * 4);

            $out = [];
            foreach ($collection as $query) {
                $text = trim((string) $query->getQueryText());
                if (mb_strlen($text) < self::MIN_QUERY_LENGTH) {
                    continue;
                }
                $out[] = [
                    'text'  => $text,
                    'url'   => $this->url->getUrl('catalogsearch/result', ['_query' => ['q' => $text]]),
                    'count' => (int) $query->getNumResults(),
                ];
                if (count($out) >= $limit) {
                    break;
                }
            }

            return $this->trending = $out;
        } catch (\Throwable $e) {
            // A failed trending list must never take the page down — the hero
            // field is the part that matters and it needs no data at all.
            $this->logger->warning('SearchLanding trending: ' . $e->getMessage());
            return $this->trending = [];
        }
    }

    /**
     * Top-level, active, menu-visible categories that have both products and an
     * image, in the merchandiser's own position order — the same order the nav
     * bar shows, so the two cannot tell the shopper different things.
     *
     * @return array<int, array{id:int,name:string,url:string,image:string,count:int}>
     */
    public function getPopularCategories(int $limit = 6): array
    {
        if ($this->categories !== null) {
            return $this->categories;
        }

        try {
            $store = $this->storeManager->getStore();

            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'image', 'url_key'])
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToFilter('include_in_menu', 1)
                ->setStoreId((int) $store->getId())
                ->addFieldToFilter('level', 2)
                ->addFieldToFilter('parent_id', (int) $store->getRootCategoryId())
                // Magento maintains the count on the collection — no per-category
                // COUNT(*), the same approach CategoryChips uses.
                ->setLoadProductCount(true)
                ->addAttributeToSort('position', 'ASC');

            $out = [];
            foreach ($collection as $category) {
                /*
                 * getImageUrl(), NOT a hand-built path. The stored attribute value
                 * on this install already contains a /media/catalog/category/
                 * prefix, so concatenating the media base URL double-prefixes it
                 * and every card comes out broken.
                 */
                $image = (string) ($category->getImageUrl() ?: '');
                $count = (int) $category->getProductCount();
                if ($image === '' || $count < 1) {
                    continue;
                }

                $out[] = [
                    'id'    => (int) $category->getId(),
                    'name'  => (string) $category->getName(),
                    'url'   => (string) $category->getUrl(),
                    'image' => $image,
                    'count' => $count,
                ];
                if (count($out) >= $limit) {
                    break;
                }
            }

            return $this->categories = $out;
        } catch (\Throwable $e) {
            $this->logger->warning('SearchLanding categories: ' . $e->getMessage());
            return $this->categories = [];
        }
    }
}
