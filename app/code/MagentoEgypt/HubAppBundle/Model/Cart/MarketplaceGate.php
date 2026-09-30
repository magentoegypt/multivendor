<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Model\Cart;

use MagentoEgypt\VendorExtend\Model\StorefrontVisibility;

/**
 * The marketplace's add-to-cart rule, for hmAddBundleToCart.
 *
 * The website refuses a product that is not approved or whose seller is not
 * active in Vnecoms\VendorsProduct\Observer\ValidateBeforeAddToCart (global
 * events.xml, checkout_cart_product_add_before). Only
 * Magento\Checkout\Model\Cart::addProduct() dispatches that event, and the
 * mutation adds through Quote::addProduct() like core's addProductsToCart, so
 * the same conditions are checked here instead of dispatching checkout events:
 *
 *   - approval: the observer accepts Vnecoms' getAllowedApprovalStatus(), which
 *     VendorExtend narrows to APPROVED on the storefront (etc/frontend/di.xml)
 *     but which would also let PENDING_UPDATE through in the graphql area; the
 *     storefront's value is used, as StorefrontVisibility does;
 *   - the product's seller is not one of Vendors\Helper\Data::getNotActiveVendorIds().
 *
 * The bundle must also be a product the storefront shows in the store view
 * (StorefrontVisibility::sellableIds(): the above plus enabled and
 * catalog-visible). Every product the customer selected inside it passes the
 * observer's two conditions (approvedIds()); catalog visibility is not asked
 * of those, bundle children are usually "Not Visible Individually". As on the
 * website, the variant chosen inside a configurable child is not checked
 * separately. StorefrontVisibility lets everything through only when the
 * marketplace's approval attribute is missing or its query fails (logged).
 */
class MarketplaceGate
{
    public function __construct(private readonly StorefrontVisibility $visibility)
    {
    }

    public function allowsBundle(int $bundleId, int $storeId): bool
    {
        return $bundleId > 0 && in_array($bundleId, $this->visibility->sellableIds([$bundleId], $storeId), true);
    }

    /**
     * @param int[] $productIds products selected inside the bundle
     */
    public function allowsSelections(array $productIds): bool
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
        if (!$ids) {
            return true;
        }
        $allowed = array_map('intval', $this->visibility->approvedIds($ids));

        return !array_diff($ids, $allowed);
    }

    /**
     * Product ids a bundle buy request adds. Pure: unit-tested.
     *
     * @param array<string, mixed> $request BundleBuyRequestBuilder::build() output
     * @param array<int, array{option_id: int, product_id: int, type_id: string}> $bundleSelections selection id => selection
     * @return int[]
     */
    public static function selectedProductIds(array $request, array $bundleSelections): array
    {
        $ids = [];
        foreach ((array) ($request['bundle_option'] ?? []) as $chosen) {
            foreach ((array) $chosen as $selectionId) {
                $productId = (int) ($bundleSelections[(int) $selectionId]['product_id'] ?? 0);
                if ($productId > 0) {
                    $ids[$productId] = $productId;
                }
            }
        }

        return array_values($ids);
    }
}
