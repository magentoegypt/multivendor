<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Data\Product;

use Magento\Framework\Api\AbstractSimpleObject;
use MagentoEgypt\VendorExtend\Api\Data\Product\TranslationsInterface;

class Translations extends AbstractSimpleObject implements TranslationsInterface
{
    public function getDefaultValues()
    {
        return (array) ($this->_get('default_values') ?? []);
    }

    public function setDefaultValues(array $values)
    {
        return $this->setData('default_values', $values);
    }

    public function getStores()
    {
        return (array) ($this->_get('stores') ?? []);
    }

    public function setStores(array $stores)
    {
        return $this->setData('stores', $stores);
    }
}
