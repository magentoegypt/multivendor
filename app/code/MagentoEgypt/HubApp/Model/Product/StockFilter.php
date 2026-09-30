<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatusResource;
use Psr\Log\LoggerInterface;

/**
 * The storefront's stock rule for product lists, as a gate on ids.
 *
 * With "Display Out of Stock Products" = No (cataloginventory/options/
 * show_out_of_stock at store scope), core's GraphQL product data provider only
 * returns products in stock: its StockProcessor calls
 * Stock\Status::addStockDataToCollection($collection, true). This asks the same
 * method about a collection of the ids, so the rankings and getList() can never
 * disagree. This install runs MSI (the Magento_Inventory* modules are enabled in
 * app/etc/config.php): InventoryCatalog's AdaptAddStockDataToCollectionPlugin
 * then resolves the stock of the current website and filters on
 * cataloginventory_stock_status.stock_status (default stock) or
 * inventory_stock_<id>.is_salable (any other stock); without MSI the legacy
 * index is used. One query per call, and none when the store lists
 * out-of-stock products.
 *
 * Fails open (all ids, logged), like the rest of the storefront gate.
 */
class StockFilter
{
    public function __construct(
        private readonly StockConfigurationInterface $stockConfiguration,
        private readonly StockStatusResource $stockStatus,
        private readonly ProductCollectionFactory $collectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Whether the store view hides out-of-stock products from lists.
     */
    public function isActive(int $storeId): bool
    {
        return !$this->stockConfiguration->isShowOutOfStock($storeId);
    }

    /**
     * The in-stock subset of $productIds, order kept; all of them when the store lists out-of-stock products.
     *
     * @param int[] $productIds
     * @return int[]
     */
    public function inStock(array $productIds, int $storeId): array
    {
        $productIds = array_values(array_map('intval', $productIds));
        if (!$productIds || !$this->isActive($storeId)) {
            return $productIds;
        }

        try {
            $collection = $this->collectionFactory->create();
            $collection->setStoreId($storeId);
            $collection->addIdFilter($productIds);
            $this->stockStatus->addStockDataToCollection($collection, true);
            $inStock = array_flip(array_map('intval', $collection->getAllIds()));
        } catch (\Throwable $e) {
            $this->logger->error('HubApp: stock filter failed, passing all ids: ' . $e->getMessage());

            return $productIds;
        }

        return array_values(array_filter($productIds, static fn (int $id): bool => isset($inStock[$id])));
    }
}
