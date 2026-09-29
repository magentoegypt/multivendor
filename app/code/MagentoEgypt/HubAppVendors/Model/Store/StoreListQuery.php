<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Seller\ListableProducts;
use MagentoEgypt\HubApp\Model\Seller\SellerDirectory;

/**
 * One page of seller cards: hmStores and the store sections of the app Home.
 *
 * Candidates are the APPROVED sellers with at least one listable product (the
 * website's New Stores rule: an empty store is worse than one card fewer).
 * Filters combine with AND; sorting and paging happen in PHP — there are a few
 * dozen sellers — and only the page's cards are completed (logo, dispatch, link).
 * The whole build runs in ONE storefront emulation, so seller names and
 * dispatch labels share it (the readers' own run() calls re-enter it).
 */
class StoreListQuery
{
    public const MAX_PAGE_SIZE = 50;

    public function __construct(
        private readonly SellerDirectory $directory,
        private readonly ListableProducts $listableProducts,
        private readonly StoreCards $cards,
        private readonly CategorySellers $categorySellers,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StorefrontEmulationInterface $emulation
    ) {
    }

    /**
     * @param array{featured?: bool|null, category_id?: int|null, name?: string|null, codes?: string[]|null} $filter
     * @param string|null $sort HmStoreSort value; null = codes order when codes are given, else FEATURED
     * @return array{items: array<int, array<string, mixed>>, total_count: int, page_info: array<string, int>}
     */
    public function execute(array $filter, ?string $sort, int $pageSize, int $currentPage, int $storeId): array
    {
        return $this->emulation->run(
            $storeId,
            fn (): array => $this->build($filter, $sort, $pageSize, $currentPage, $storeId)
        );
    }

    /**
     * @param array<string, mixed> $filter
     * @return array{items: array<int, array<string, mixed>>, total_count: int, page_info: array<string, int>}
     */
    private function build(array $filter, ?string $sort, int $pageSize, int $currentPage, int $storeId): array
    {
        $pageSize = max(1, min(self::MAX_PAGE_SIZE, $pageSize));
        $currentPage = max(1, $currentPage);

        $codes = array_values(array_filter(
            array_map(static fn ($code): string => trim((string) $code), (array) ($filter['codes'] ?? [])),
            static fn (string $code): bool => $code !== ''
        ));
        //  An empty `codes` list is no filter; a list of unknown codes matches nobody.
        $ids = (!empty($filter['codes']) || $codes)
            ? $this->directory->approvedIdsForCodes($codes)
            : $this->directory->approvedIds();

        $listable = array_filter(
            $this->listableProducts->forVendors($ids, $storeId),
            static fn (array $productIds): bool => $productIds !== []
        );
        $ids = array_values(array_intersect($ids, array_keys($listable)));

        if ($ids && isset($filter['category_id'])) {
            $ids = array_values(array_intersect(
                $ids,
                $this->categorySellers->vendorIds(
                    (int) $filter['category_id'],
                    array_intersect_key($listable, array_flip($ids))
                )
            ));
        }

        $cards = $this->cards->summaries($ids, $storeId);

        if (isset($filter['featured']) && $filter['featured'] !== null) {
            $wanted = (bool) $filter['featured'];
            $cards = array_filter($cards, static fn (array $card): bool => $card['is_featured'] === $wanted);
        }

        $needle = trim((string) ($filter['name'] ?? ''));
        if ($needle !== '') {
            $cards = array_filter(
                $cards,
                static fn (array $card): bool => mb_stripos((string) $card['name'], $needle, 0, 'UTF-8') !== false
                    || mb_stripos((string) $card['code'], $needle, 0, 'UTF-8') !== false
            );
        }

        $sorted = StoreSorter::sort($cards, $sort, $codes, $this->nameComparator($storeId));
        $total = count($sorted);
        $page = array_slice($sorted, ($currentPage - 1) * $pageSize, $pageSize);

        return [
            'items' => $this->cards->complete($page, $storeId),
            'total_count' => $total,
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'total_pages' => (int) ceil($total / $pageSize),
            ],
        ];
    }

    /**
     * Name order of the store view's language (Arabic letters in Arabic order), natural and
     * case-insensitive otherwise.
     *
     * @return callable(string, string): int
     */
    private function nameComparator(int $storeId): callable
    {
        if (class_exists(\Collator::class)) {
            $locale = (string) $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId);
            $collator = \Collator::create($locale !== '' ? $locale : 'en_US');
            if ($collator instanceof \Collator) {
                $collator->setStrength(\Collator::SECONDARY);
                $collator->setAttribute(\Collator::NUMERIC_COLLATION, \Collator::ON);

                return static fn (string $a, string $b): int => (int) $collator->compare($a, $b);
            }
        }

        return static fn (string $a, string $b): int => strnatcasecmp($a, $b);
    }
}
