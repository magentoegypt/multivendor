<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Api\Data\Product;

/**
 * One store view's own values of a product's translatable attributes (vendor app product form).
 */
interface StoreTranslationInterface
{
    /**
     * @return int|null
     */
    public function getStoreId();

    /**
     * @param int|null $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Store view code, e.g. "en" or "ar". The key a PUT is matched on.
     *
     * @return string|null
     */
    public function getStoreCode();

    /**
     * @param string|null $storeCode
     * @return $this
     */
    public function setStoreCode($storeCode);

    /**
     * @return string|null
     */
    public function getStoreName();

    /**
     * @param string|null $storeName
     * @return $this
     */
    public function setStoreName($storeName);

    /**
     * Store view locale, e.g. "ar_SA".
     *
     * @return string|null
     */
    public function getLocale();

    /**
     * @param string|null $locale
     * @return $this
     */
    public function setLocale($locale);

    /**
     * The store's own values; a null value means the store has none and shows the default.
     *
     * @return \Magento\Framework\Api\AttributeInterface[]
     */
    public function getValues();

    /**
     * @param \Magento\Framework\Api\AttributeInterface[] $values
     * @return $this
     */
    public function setValues(array $values);
}
