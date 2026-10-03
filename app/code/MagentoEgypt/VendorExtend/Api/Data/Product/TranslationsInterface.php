<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Api\Data\Product;

/**
 * A product's translatable text: the default (store 0) values and each store view's own values.
 */
interface TranslationsInterface
{
    /**
     * Store 0 values: what every store view shows when it has no value of its own.
     *
     * @return \Magento\Framework\Api\AttributeInterface[]
     */
    public function getDefaultValues();

    /**
     * @param \Magento\Framework\Api\AttributeInterface[] $values
     * @return $this
     */
    public function setDefaultValues(array $values);

    /**
     * @return \MagentoEgypt\VendorExtend\Api\Data\Product\StoreTranslationInterface[]
     */
    public function getStores();

    /**
     * @param \MagentoEgypt\VendorExtend\Api\Data\Product\StoreTranslationInterface[] $stores
     * @return $this
     */
    public function setStores(array $stores);
}
