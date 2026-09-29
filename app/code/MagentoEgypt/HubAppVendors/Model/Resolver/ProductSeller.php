<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\DataObject;

/**
 * ProductInterface.hm_seller — "sold by" for every product of a response in one batch.
 *
 * The seller is catalog_product_entity.vendor_id, a static column that product
 * values carry from e.*; the product model is the second source and a single
 * query the last. Anonymous and cacheable (SellerIdentity: hm_vendor_<id>).
 */
class ProductSeller extends AbstractSellerResolver
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
            $model = $value['model'] ?? null;
            if (array_key_exists('vendor_id', $value)) {
                $out[$key] = (int) $value['vendor_id'];
            } elseif ($model instanceof DataObject && $model->hasData('vendor_id')) {
                $out[$key] = (int) $model->getData('vendor_id');
            } else {
                $productId = (int) ($value['entity_id'] ?? ($model instanceof DataObject ? $model->getId() : 0));
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
