<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Offer;

use Magento\Catalog\Model\Product;
use Magento\CatalogGraphQl\Model\Resolver\Product\Price\ProviderPool as PriceProviderPool;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product as ProductDataProvider;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\GraphQl\Model\Query\ContextInterface as QueryContextInterface;
use Psr\Log\LoggerInterface;

/**
 * The offer products' own data — SKU, URL key, type, stock, price — for the
 * request's store view and customer group.
 *
 * Loaded the way core loads any product list (CatalogGraphQl's product data
 * provider, as HubApp's ProductListLoader does: the store's website, enabled,
 * catalog-visible, stock data joined) in ONE collection, then priced by core's
 * own price providers, the ones price_range uses. An offer therefore shows
 * exactly what price_range.minimum_price says on the offer's own page; like
 * price_range, a product's price info may read its tier and rule prices.
 */
class OfferProducts
{
    /** The GraphQL fields the collection is prepared for (core maps them to attributes). */
    private const FIELDS = ['sku', 'url_key', 'price_range'];

    public function __construct(
        private readonly ProductDataProvider $productDataProvider,
        private readonly SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        private readonly PriceProviderPool $priceProviders,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly Uid $uid,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $productIds
     * @return array<int, array{
     *     uid: string, sku: string, url_key: string, type_id: string, in_stock: bool,
     *     price: array{value: float, currency: string}, regular_price: array{value: float, currency: string}
     * }> by product id; products the store does not serve are absent
     */
    public function load(array $productIds, ContextInterface $context): array
    {
        $ids = FamilyReader::normalise($productIds);
        if (!$ids || !$context instanceof QueryContextInterface) {
            return [];
        }
        $extension = $context->getExtensionAttributes();
        $currency = (string) $extension->getStore()->getCurrentCurrencyCode();
        $customerGroupId = $extension->getCustomerGroupId();

        try {
            $criteria = $this->searchCriteriaBuilderFactory->create()
                ->addFilter('entity_id', $ids, 'in')
                ->setPageSize(count($ids))
                ->setCurrentPage(1)
                ->create();
            $items = $this->productDataProvider->getList($criteria, self::FIELDS, false, false, $context)->getItems();
        } catch (\Throwable $e) {
            $this->logger->error('HubApp: other sellers\' offers could not be loaded: ' . $e->getMessage());

            return [];
        }

        $out = [];
        foreach ($items as $product) {
            if (!$product instanceof Product) {
                continue;
            }
            $id = (int) $product->getId();
            $urlKey = trim((string) $product->getData('url_key'));
            if ($id <= 0 || $urlKey === '') {
                continue;
            }
            if ($customerGroupId !== null) {
                //  As price_range does (PriceRangeDataProvider): prices of the caller's group.
                $product->setCustomerGroupId((int) $customerGroupId);
            }
            try {
                $provider = $this->priceProviders->getProviderByProductType((string) $product->getTypeId());
                $regular = (float) $provider->getMinimalRegularPrice($product)->getValue();
                $final = (float) $provider->getMinimalFinalPrice($product)->getValue();
            } catch (\Throwable $e) {
                $this->logger->warning(sprintf('HubApp: offer %d has no price: %s', $id, $e->getMessage()));
                continue;
            }

            $out[$id] = [
                'uid' => $this->uid->encode((string) $id),
                'sku' => (string) $product->getSku(),
                'url_key' => $urlKey,
                'type_id' => (string) $product->getTypeId(),
                //  Stock data joined by the provider (Stock\Status::addStockDataToCollection, MSI-aware).
                'in_stock' => $product->hasData('is_salable')
                    ? (bool) $product->getData('is_salable')
                    : (bool) $product->isSalable(),
                'price' => ['value' => $this->priceCurrency->round($final), 'currency' => $currency],
                'regular_price' => ['value' => $this->priceCurrency->round($regular), 'currency' => $currency],
            ];
        }

        return $out;
    }
}
