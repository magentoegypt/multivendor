<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Api;

/**
 * Store-view text of the calling seller's products, for the vendor app's product form.
 *
 * Bound to the seller's own customer token: every route forces customerId from %customer_id%.
 */
interface ProductTranslationsInterface
{
    /**
     * GET /V1/vendors/product/:sku/translations — default and per-store values of one of the caller's products.
     *
     * @param int $customerId
     * @param string $sku
     * @return \MagentoEgypt\VendorExtend\Api\Data\Product\TranslationsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException another seller's or a missing SKU
     */
    public function get($customerId, $sku);

    /**
     * GET /V1/vendors/product/translations — the same shape for a product not created yet, every value null.
     *
     * @param int $customerId
     * @return \MagentoEgypt\VendorExtend\Api\Data\Product\TranslationsInterface
     */
    public function getForNewProduct($customerId);

    /**
     * PUT /V1/vendors/product/:sku/translations — save store-view values (null or "" removes the store value).
     *
     * Follows the update approval rule: on an approved product the change waits for admin review.
     *
     * @param int $customerId
     * @param string $sku
     * @param \MagentoEgypt\VendorExtend\Api\Data\Product\StoreTranslationInterface[] $translations
     * @return \MagentoEgypt\VendorExtend\Api\Data\Product\TranslationsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException another seller's or a missing SKU
     * @throws \Magento\Framework\Exception\InputException unknown store or attribute
     */
    public function save($customerId, $sku, array $translations);
}
