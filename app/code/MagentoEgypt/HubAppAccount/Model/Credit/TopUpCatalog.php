<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Credit;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\VendorExtend\Model\StorefrontVisibility;
use Psr\Log\LoggerInterface;
use Vnecoms\Credit\Model\Product\Type\Credit as CreditProductType;

/**
 * The store credit products a store view sells, as TopUpOptions.
 *
 * The same products the website sells: its Buy Credit page (Vnecoms\Credit\Block\Credit\Product\ListProduct)
 * lists the store_credit products visible in the catalogue, and its add-to-cart
 * (Vnecoms\VendorsProduct\Observer\ValidateBeforeAddToCart) takes only approved products of active sellers.
 * So: type store_credit, in the store's website, enabled, catalogue-visible, approved and of an active
 * seller (VendorExtend StorefrontVisibility::sellableIds), and saleable (in stock).
 *
 * Remembered per store view for the request: hmStoreCredit.top_up and hmAddCreditToCart read it once.
 */
class TopUpCatalog
{
    /** Far more than a Buy Credit page lists; anything past it is misconfiguration, not an offer. */
    private const MAX_PRODUCTS = 50;

    /** @var array<int, TopUpOptions|null> store id => options */
    private array $byStore = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StorefrontVisibility $visibility,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * What the store view sells; null when it sells no credit.
     */
    public function forStore(int $storeId): ?TopUpOptions
    {
        if (!array_key_exists($storeId, $this->byStore)) {
            $this->byStore[$storeId] = $this->load($storeId);
        }

        return $this->byStore[$storeId];
    }

    /**
     * A fresh, private copy of credit product $productId for a cart of store view $storeId, or null when
     * that store view cannot sell it (any more).
     */
    public function productForCart(int $productId, int $storeId): ?Product
    {
        try {
            $loaded = $this->productRepository->getById($productId, false, $storeId, true);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        if (!$loaded instanceof Product
            || $loaded->getTypeId() !== CreditProductType::TYPE_CODE
            || $this->visibility->sellableIds([$productId], $storeId) !== [$productId]
        ) {
            return null;
        }
        //  prepareForCart() adds custom options to the product: never on the repository's shared copy.
        $product = clone $loaded;

        return $this->saleable($product, $storeId) ? $product : null;
    }

    public function currency(int $storeId): string
    {
        try {
            return (string) $this->storeManager->getStore($storeId)->getBaseCurrencyCode();
        } catch (\Throwable $e) {
            return (string) $this->storeManager->getStore()->getBaseCurrencyCode();
        }
    }

    private function load(int $storeId): ?TopUpOptions
    {
        try {
            $websiteId = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
            $ids = $this->creditProductIds($websiteId);
            if (!$ids) {
                return null;
            }
            $products = [];
            foreach ($this->visibility->sellableIds($ids, $storeId) as $id) {
                try {
                    $product = $this->productRepository->getById((int) $id, false, $storeId);
                } catch (NoSuchEntityException $e) {
                    continue;
                }
                if (!$product instanceof Product || !$this->saleable($product, $storeId)) {
                    continue;
                }
                $products[] = [
                    'id' => (int) $product->getId(),
                    'sku' => (string) $product->getSku(),
                    'credit_type' => $product->getData('credit_type'),
                    'credit_value_fixed' => $product->getData('credit_value_fixed'),
                    'credit_price' => $product->getData('credit_price'),
                    'credit_value_dropdown' => $product->getData('credit_value_dropdown'),
                    'credit_value_custom' => $product->getData('credit_value_custom'),
                    'credit_rate' => $product->getData('credit_rate'),
                ];
            }

            return TopUpOptions::fromProducts($products);
        } catch (\Throwable $e) {
            //  A top-up that cannot be read is not offered; the balance and transactions still answer.
            $this->logger->warning('HubAppAccount: store credit products not read: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @return int[] store_credit products of the website, oldest first
     */
    private function creditProductIds(int $websiteId): array
    {
        $connection = $this->resource->getConnection();

        return array_map('intval', $connection->fetchCol(
            $connection->select()
                ->from(['e' => $this->resource->getTableName('catalog_product_entity')], ['entity_id'])
                ->joinInner(
                    ['w' => $this->resource->getTableName('catalog_product_website')],
                    'w.product_id = e.entity_id',
                    []
                )
                ->where('e.type_id = ?', CreditProductType::TYPE_CODE)
                ->where('w.website_id = ?', $websiteId)
                ->order('e.entity_id ASC')
                ->limit(self::MAX_PRODUCTS)
        ));
    }

    /**
     * In the store's website and saleable there (enabled, in stock).
     */
    private function saleable(Product $product, int $storeId): bool
    {
        try {
            $websiteId = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
        } catch (\Throwable $e) {
            return false;
        }

        return in_array($websiteId, array_map('intval', (array) $product->getWebsiteIds()), true)
            && $product->isSaleable()
            && $product->isAvailable();
    }
}
