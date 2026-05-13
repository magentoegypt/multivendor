<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Api\Data;

/**
 * @api
 */
interface CategoryProductLinkInterface
{
    /**
     * @return string|null
     */
    public function getSku();

    /**
     * @param string $sku
     *
     * @return $this
     */
    public function setSku($sku);

    /**
     * @return int|null
     */
    public function getPosition();

    /**
     * @param int $position
     *
     * @return $this
     */
    public function setPosition($position);

    /**
     * Get category id.
     *
     * @return string
     */
    public function getCategoryId();

    /**
     * Set category id.
     *
     * @param string $categoryId
     *
     * @return $this
     */
    public function setCategoryId($categoryId);
}
