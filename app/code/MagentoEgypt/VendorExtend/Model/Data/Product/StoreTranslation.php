<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Data\Product;

use Magento\Framework\Api\AbstractSimpleObject;
use MagentoEgypt\VendorExtend\Api\Data\Product\StoreTranslationInterface;

class StoreTranslation extends AbstractSimpleObject implements StoreTranslationInterface
{
    public function getStoreId()
    {
        $id = $this->_get('store_id');
        return $id === null ? null : (int) $id;
    }

    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }

    public function getStoreCode()
    {
        return $this->_get('store_code');
    }

    public function setStoreCode($storeCode)
    {
        return $this->setData('store_code', $storeCode);
    }

    public function getStoreName()
    {
        return $this->_get('store_name');
    }

    public function setStoreName($storeName)
    {
        return $this->setData('store_name', $storeName);
    }

    public function getLocale()
    {
        return $this->_get('locale');
    }

    public function setLocale($locale)
    {
        return $this->setData('locale', $locale);
    }

    public function getValues()
    {
        return (array) ($this->_get('values') ?? []);
    }

    public function setValues(array $values)
    {
        return $this->setData('values', $values);
    }
}
