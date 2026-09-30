<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Product;

use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product as ProductDataProvider;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\GraphQl\Model\Query\ContextInterface as QueryContextInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;
use MagentoEgypt\VendorExtend\Model\StorefrontVisibility;
use Psr\Log\LoggerInterface;

/**
 * Ranked ids -> ProductInterface values, through core's GraphQL product data provider.
 *
 * Same pattern as core's related / upsell / cross-sell batch resolvers
 * (RelatedProductGraphQl AbstractLikedProducts): one collection for the whole
 * list with exactly the attributes the query asked for, price data for the
 * customer group of the request, then back into rank order — IN() does not keep
 * the order of its list.
 */
class ProductListLoader implements ProductListLoaderInterface, ResetAfterRequestInterface
{
    /** One query can never ask for more than this many products. */
    private const MAX_IDS = 500;

    /**
     * Gate answers already known in this request: store id => product id => allowed.
     * A Home build gates each section's ranking and the products field gates the
     * same ids again at load time; the second pass costs no query.
     *
     * @var array<int, array<int, bool>>
     */
    private array $gate = [];

    public function __construct(
        private readonly ProductDataProvider $productDataProvider,
        private readonly SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        private readonly StorefrontVisibility $visibility,
        private readonly StockFilter $stockFilter,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sellable(array $productIds, int $storeId): array
    {
        $ids = self::normalise($productIds);
        if (!$ids) {
            return [];
        }

        $unknown = array_values(array_filter($ids, fn (int $id): bool => !isset($this->gate[$storeId][$id])));
        if ($unknown) {
            //  Approved, active seller, enabled and catalog-visible in this store …
            $allowed = $this->visibility->sellableIds($unknown, $storeId);
            //  … not another seller's "select and sell" copy of it …
            $allowed = $this->visibility->searchableIds($allowed);
            //  … and in stock when the store hides out-of-stock products (getList() filters them too).
            $allowed = array_flip($this->stockFilter->inStock($allowed, $storeId));
            foreach ($unknown as $id) {
                $this->gate[$storeId][$id] = isset($allowed[$id]);
            }
        }

        return array_values(array_filter($ids, fn (int $id): bool => $this->gate[$storeId][$id]));
    }

    /**
     * @inheritDoc
     */
    public function load(array $productIds, array $fields, ContextInterface $context): array
    {
        $store = $context->getExtensionAttributes()->getStore();
        $storeId = (int) $store->getId();

        $ids = array_slice($this->sellable($productIds, $storeId), 0, self::MAX_IDS);
        if (!$ids) {
            return [];
        }

        try {
            $criteria = $this->searchCriteriaBuilderFactory->create()
                ->addFilter('entity_id', $ids, 'in')
                ->setPageSize(count($ids))
                ->setCurrentPage(1)
                ->create();
            $result = $this->productDataProvider->getList(
                $criteria,
                array_values(array_unique(array_filter(array_map('strval', $fields)))),
                false,
                false,
                //  The data provider takes the GraphQl module's context (customer
                //  group prices); every resolver context in the graphql area is one.
                $context instanceof QueryContextInterface ? $context : null
            );
        } catch (\Throwable $e) {
            $this->logger->error('HubApp: product list load failed: ' . $e->getMessage());

            return [];
        }

        $byId = [];
        foreach ($result->getItems() as $product) {
            $byId[(int) $product->getId()] = $product;
        }

        $out = [];
        foreach ($ids as $id) {
            if (!isset($byId[$id])) {
                continue;
            }
            $data = $byId[$id]->getData();
            //  The Product object wins over a user attribute named `model`, as in core.
            $data['model'] = $byId[$id];
            $out[] = $data;
        }

        return $out;
    }

    /**
     * Positive, unique ints in the given order.
     *
     * @param array<int|string, mixed> $ids
     * @return int[]
     */
    public static function normalise(array $ids): array
    {
        $out = [];
        $seen = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0 && !isset($seen[$id])) {
                $seen[$id] = true;
                $out[] = $id;
            }
        }

        return $out;
    }

    /**
     * Per-request memo only; nothing survives a request in a long-running process.
     */
    public function _resetState(): void
    {
        $this->gate = [];
    }
}
