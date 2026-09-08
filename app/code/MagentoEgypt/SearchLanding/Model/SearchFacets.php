<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SearchLanding\Model;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * The vendor and category matches behind the search tabs.
 *
 * Extracted so the live panel on /search and the header on the full results page
 * report the SAME numbers. When each had its own copy the two disagreed, which on
 * a tab labelled "Vendors (4)" is not a cosmetic difference — it is the count the
 * shopper is deciding whether to click.
 *
 * Every lookup is capped and wrapped: these run on keystrokes through the live
 * panel, and the www FPM pool on this host allows five children.
 */
class SearchFacets
{
    public const VENDOR_LIMIT = 6;
    public const CATEGORY_LIMIT = 6;

    /** @var array<string, array<int, array{id:int,name:string,url:string,city:?string}>> */
    private array $vendorCache = [];

    /** @var array<string, array<int, array{id:int,name:string,url:string,count:int}>> */
    private array $categoryCache = [];

    public function __construct(
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ResourceConnection $resource,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $url,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Vendors whose company name matches.
     *
     * Read straight off ves_vendor_entity rather than through the Vnecoms vendor
     * collection: that collection loads the full EAV row set per vendor, and this
     * can run on keystrokes. There are 25 vendor rows on this install, so a LIKE
     * against a plain column is the cheapest correct answer.
     *
     * @return array<int, array{id:int,name:string,url:string,city:?string}>
     */
    public function getVendors(string $query, int $limit = self::VENDOR_LIMIT): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $key = $query . '|' . $limit;
        if (isset($this->vendorCache[$key])) {
            return $this->vendorCache[$key];
        }

        try {
            $connection = $this->resource->getConnection();

            /*
             * STATUS_APPROVED is 2, not 1. Vnecoms numbers these pending=1,
             * approved=2, disabled=3, expired=4 — filtering on 1 offers vendors
             * who have not been approved and hides every real one.
             */
            $select = $connection->select()
                ->from($this->resource->getTableName('ves_vendor_entity'), ['vendor_id', 'company', 'city'])
                ->where('company LIKE ?', '%' . $this->escapeLike($query) . '%')
                ->where('status = ?', \Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED)
                ->limit($limit);

            $out = [];
            foreach ($connection->fetchAll($select) as $row) {
                $name = trim((string) $row['company']);
                if ($name === '') {
                    continue;
                }
                $out[] = [
                    'id'   => (int) $row['vendor_id'],
                    'name' => $name,
                    // /shop/<vendor_id>, the pattern the homepage store cards use.
                    'url'  => $this->url->getUrl('shop/' . $row['vendor_id']),
                    'city' => ($row['city'] ?? '') !== '' ? (string) $row['city'] : null,
                ];
            }

            return $this->vendorCache[$key] = $out;
        } catch (\Throwable $e) {
            $this->logger->warning('SearchFacets vendors: ' . $e->getMessage());
            return $this->vendorCache[$key] = [];
        }
    }

    /**
     * Categories whose name matches, restricted to ones a shopper can reach.
     *
     * @return array<int, array{id:int,name:string,url:string,count:int}>
     */
    public function getCategories(string $query, int $limit = self::CATEGORY_LIMIT): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $key = $query . '|' . $limit;
        if (isset($this->categoryCache[$key])) {
            return $this->categoryCache[$key];
        }

        try {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'url_key'])
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToFilter('include_in_menu', 1)
                ->addAttributeToFilter('name', ['like' => '%' . $this->escapeLike($query) . '%'])
                ->setStoreId((int) $this->storeManager->getStore()->getId())
                ->setLoadProductCount(true)
                ->setPageSize($limit);

            $out = [];
            foreach ($collection as $category) {
                $out[] = [
                    'id'    => (int) $category->getId(),
                    'name'  => (string) $category->getName(),
                    'url'   => (string) $category->getUrl(),
                    'count' => (int) $category->getProductCount(),
                ];
            }

            return $this->categoryCache[$key] = $out;
        } catch (\Throwable $e) {
            $this->logger->warning('SearchFacets categories: ' . $e->getMessage());
            return $this->categoryCache[$key] = [];
        }
    }

    /**
     * Neutralise LIKE wildcards in visitor input.
     *
     * Placeholder binding stops SQL injection but does NOT stop `%` and `_` being
     * read as wildcards, so a search for "_" would otherwise match every vendor
     * and every category. The escape character is escaped first, so a trailing
     * backslash cannot swallow the wildcard after it.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
