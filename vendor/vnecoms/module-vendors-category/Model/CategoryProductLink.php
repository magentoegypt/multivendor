<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model;

/**
 * @codeCoverageIgnore
 */
class CategoryProductLink extends \Magento\Framework\DataObject implements
    \Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface
{
    /**#@+
     * Constant for confirmation status
     */
    const KEY_SKU = 'sku';
    const KEY_POSITION = 'position';
    const KEY_CATEGORY_ID = 'category_id';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function getSku()
    {
        return $this->getData(self::KEY_SKU);
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return $this->getData(self::KEY_POSITION);
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryId()
    {
        return $this->getData(self::KEY_CATEGORY_ID);
    }

    /**
     * @param string $sku
     *
     * @return $this
     */
    public function setSku($sku)
    {
        return $this->setData(self::KEY_SKU, $sku);
    }

    /**
     * @param int $position
     *
     * @return $this
     */
    public function setPosition($position)
    {
        return $this->setData(self::KEY_POSITION, $position);
    }

    /**
     * Set category id.
     *
     * @param string $categoryId
     *
     * @return $this
     */
    public function setCategoryId($categoryId)
    {
        return $this->setData(self::KEY_CATEGORY_ID, $categoryId);
    }
}
