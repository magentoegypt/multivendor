<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\DataObject;

/**
 * CartItemInterface.hm_seller — the seller of each cart line, for every line in one batch.
 *
 * quote_item.vendor_id is the line's seller: Vnecoms sets it on save
 * (VendorsSales QuoteItemSaveBefore) and lets other modules change it through
 * ves_vendors_checkout_init_vendor_id, e.g. another seller's offer on the same
 * product. A line not saved yet, or saved with 0, falls back to the product's
 * owner. The app groups its cart by hm_seller.code (null = Hub Market).
 *
 * No @cache: carts are POSTed and per customer; the cart query itself decides
 * who may read it (GetCartForUser).
 */
class CartItemSeller extends AbstractSellerResolver
{
    /**
     * @inheritDoc
     */
    protected function vendorIds(array $requests): array
    {
        $out = [];
        $lookup = [];
        foreach ($requests as $key => $request) {
            $value = (array) ($request->getValue() ?? []);
            $item = $value['model'] ?? null;
            $product = (array) ($value['product'] ?? []);

            $vendorId = $item instanceof DataObject ? (int) $item->getData('vendor_id') : 0;
            if ($vendorId > 0) {
                $out[$key] = $vendorId;
                continue;
            }

            $productModel = $item instanceof DataObject ? $item->getData('product') : null;
            if (array_key_exists('vendor_id', $product)) {
                $out[$key] = (int) $product['vendor_id'];
            } elseif ($productModel instanceof DataObject && $productModel->hasData('vendor_id')) {
                $out[$key] = (int) $productModel->getData('vendor_id');
            } else {
                $productId = (int) ($product['entity_id']
                    ?? ($item instanceof DataObject ? $item->getData('product_id') : 0));
                $out[$key] = null;
                if ($productId > 0) {
                    $lookup[$key] = $productId;
                }
            }
        }

        if ($lookup) {
            $vendors = $this->productVendors->forProducts(array_values($lookup));
            foreach ($lookup as $key => $productId) {
                $out[$key] = $vendors[$productId] ?? null;
            }
        }

        return $out;
    }
}
