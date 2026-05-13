<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Plugin\Model\ResourceModel;

class Product
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    public function __construct(\Magento\Framework\App\ResourceConnection $connection)
    {
        $this->_resource = $connection;
    }

    /**
     * Set vendor object after the product is loaded.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int|string                     $modelId
     * @param string                         $field
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetVesCategoryIds(\Magento\Catalog\Model\Product $product, $result)
    {
        //        $connection = $this->_resource->getConnection();
//
//        $select = $connection->select()->from(
//            'ves_vendorscategory_category_product',
//            'category_id'
//        )->where(
//            'product_id = ?',
//            (int)$product->getId()
//        );
//
//        $result = $connection->fetchCol($select);
//
//        return $result;
    }
}
