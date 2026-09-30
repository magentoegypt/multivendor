<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Model\Pricing;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Api\Data\StoreInterface;
use MagentoEgypt\HubAppBundle\Model\Cart\BundleBuyRequestBuilder;
use MagentoEgypt\HubAppBundle\Model\Cart\BundleSelectionReader;
use MagentoEgypt\HubAppBundle\Model\Cart\MarketplaceGate;
use MagentoEgypt\HubAppBundle\Model\Cart\SuperAttributeKeeper;
use Psr\Log\LoggerInterface;

/**
 * hmBundleQuote: a package priced the way the cart will price it, without a cart.
 *
 * The same steps as hmAddBundleToCart up to the moment it would touch the cart:
 *
 *   1. the bundle, loaded for the store view, saleable on its website, through
 *      the marketplace gate (else "not found", as the website says);
 *   2. the buy request the website's bundle page posts (BundleBuyRequestBuilder),
 *      every choice checked against the bundle's own selections, and every
 *      selected product through the marketplace gate;
 *   3. the bundle type's prepareForCartAdvanced() in full mode, which is what
 *      Quote::addProduct() runs first: required options, radio/select rules,
 *      saleable selections, configurable choices (SuperAttributeKeeper keeps
 *      each configurable child's own choice, as for the cart);
 *   4. the price: the bundle's own price model on the prepared product,
 *      getFinalPrice(qty), for the caller's customer group. That is the
 *      figure the cart's subtotal collector charges for the bundle line
 *      (Quote\Address\Total\Subtotal::_initItem() on the parent item): fixed
 *      or dynamic price, the percent special price, tier prices and catalog
 *      price rules (catalog_product_get_final_price), custom options, and
 *      BundleExtend's per-option discounts on each selection
 *      (ApplyBundleDiscount on getSelectionFinalTotalPrice()).
 *
 * Cart price rules, tax and shipping are not part of a line price, so not of
 * the quote either.
 *
 * The regular total is what the same items cost bought separately: each
 * selected product's own regular price (a configurable child's chosen
 * variant) times its quantity in the package, as the bundle cards compare.
 *
 * Nothing is saved: the product is a private copy, and no quote is created.
 */
class PackagePricer
{
    private const BUNDLE_TYPES = ['bundle', 'new_bundle'];

    /** Where the result keeps the product ids it depends on, for the cache identity. */
    public const IDS_KEY = '_product_ids';

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly BundleSelectionReader $selectionReader,
        private readonly BundleBuyRequestBuilder $buyRequestBuilder,
        private readonly SuperAttributeKeeper $superAttributeKeeper,
        private readonly MarketplaceGate $marketplaceGate,
        private readonly DataObjectFactory $dataObjectFactory,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $selections HmBundleSelectionInput values
     * @return array<string, mixed> HmBundleQuote value, plus IDS_KEY
     */
    public function quote(string $sku, float $quantity, array $selections, StoreInterface $store, int $customerGroupId): array
    {
        $storeId = (int) $store->getId();
        $product = $this->loadBundle($sku, $store);
        //  Unapproved, or its seller inactive: as unknown as on the website.
        if ($product === null || !$this->marketplaceGate->allowsBundle((int) $product->getId(), $storeId)) {
            return self::unavailable(
                $quantity,
                (string) __('Could not find a product with SKU "%sku"', ['sku' => $sku]),
                []
            );
        }
        $ids = [(int) $product->getId()];
        if (!in_array((string) $product->getData('type_id'), self::BUNDLE_TYPES, true)) {
            return self::unavailable($quantity, (string) __('Product "%1" is not a bundle.', $sku), $ids);
        }

        $bundleSelections = $this->selectionReader->forBundle($product);
        try {
            $request = $this->buyRequestBuilder->build(
                (int) $product->getId(),
                $quantity,
                array_values($selections),
                $bundleSelections
            );
        } catch (LocalizedException $e) {
            return self::unavailable($quantity, $e->getMessage(), $ids);
        }
        $selected = MarketplaceGate::selectedProductIds($request, $bundleSelections);
        $ids = array_values(array_unique(array_merge($ids, $selected)));
        if (!$this->marketplaceGate->allowsSelections($selected)) {
            return self::unavailable($quantity, (string) __('The options you selected are not available.'), $ids);
        }

        $buyRequest = $this->dataObjectFactory->create(['data' => $request]);
        $this->superAttributeKeeper->remember($buyRequest);
        try {
            $product->setCustomerGroupId($customerGroupId);
            $candidates = $product->getTypeInstance()
                ->prepareForCartAdvanced($buyRequest, $product, AbstractType::PROCESS_MODE_FULL);
            if (is_string($candidates) || !is_array($candidates) || !$candidates) {
                $message = is_string($candidates) && trim($candidates) !== ''
                    ? $candidates
                    : (string) __('The options you selected are not available.');

                return self::unavailable($quantity, $message, $ids);
            }

            $children = [];
            foreach ($candidates as $candidate) {
                if ($candidate instanceof Product && $candidate !== $product) {
                    $children[] = $candidate;
                }
            }
            $unitBase = (float) $product->getPriceModel()->getFinalPrice($quantity, $product);
            $regularBase = self::regularTotal($children);
        } catch (LocalizedException $e) {
            return self::unavailable($quantity, $e->getMessage(), $ids);
        } catch (\Throwable $e) {
            $this->logger->error('HubApp: hmBundleQuote failed for ' . $sku . ': ' . $e->getMessage());

            return self::unavailable($quantity, (string) __('This bundle cannot be priced right now.'), $ids);
        } finally {
            $this->superAttributeKeeper->forget($buyRequest);
        }

        $totals = PackageTotals::of(
            (float) $this->priceCurrency->convert($unitBase, $store),
            $quantity,
            $regularBase !== null ? (float) $this->priceCurrency->convert($regularBase, $store) : null
        );
        $currency = (string) $store->getCurrentCurrencyCode();
        $money = static fn (?float $value): ?array => $value === null ? null : ['value' => $value, 'currency' => $currency];

        return [
            'available' => true,
            'message' => null,
            'quantity' => $quantity,
            'price' => $money($totals['price']),
            'row_total' => $money($totals['row_total']),
            'regular_total' => $money($totals['regular_total']),
            'saving' => $money($totals['saving']),
            'discount_percent' => $totals['discount_percent'],
            self::IDS_KEY => $ids,
        ];
    }

    /**
     * One package's selected products at their regular prices, in base currency:
     * each child's own price (a configurable child's chosen variant) times its
     * quantity in the package. Null when a price is missing: no comparison rather
     * than a wrong one (the bundle cards' rule).
     *
     * @param Product[] $children prepared bundle children (cart candidates after the bundle itself)
     */
    public static function regularTotal(array $children): ?float
    {
        if (!$children) {
            return null;
        }
        $total = 0.0;
        foreach ($children as $child) {
            $priced = $child;
            $variant = $child->getCustomOption('simple_product');
            if ($variant !== null && $variant->getProduct() instanceof Product) {
                $priced = $variant->getProduct();
            }
            $price = (float) $priced->getData('price');
            $qty = (float) ($child->getCartQty() ?: 1);
            if ($price <= 0) {
                return null;
            }
            $total += $price * $qty;
        }

        return $total;
    }

    /**
     * @param int[] $ids
     * @return array<string, mixed>
     */
    public static function unavailable(float $quantity, string $message, array $ids): array
    {
        return [
            'available' => false,
            'message' => $message,
            'quantity' => $quantity,
            'price' => null,
            'row_total' => null,
            'regular_total' => null,
            'saving' => null,
            'discount_percent' => 0,
            self::IDS_KEY => $ids,
        ];
    }

    /**
     * The bundle as a fresh, private copy for this store view, or null when its
     * website cannot sell it (hmAddBundleToCart's rule).
     */
    private function loadBundle(string $sku, StoreInterface $store): ?Product
    {
        try {
            $loaded = $this->productRepository->get($sku, false, (int) $store->getId(), true);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        if (!$loaded instanceof Product) {
            return null;
        }
        //  prepareForCart() adds custom options to the product: never on the repository's shared copy.
        $product = clone $loaded;

        $websiteId = (int) $store->getWebsiteId();
        if (!in_array($websiteId, array_map('intval', (array) $product->getWebsiteIds()), true)
            || !$product->isSaleable()
            || !$product->isAvailable()
        ) {
            return null;
        }

        return $product;
    }
}
