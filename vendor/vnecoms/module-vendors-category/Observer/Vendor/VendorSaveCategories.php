<?php
/**
 * *
 *  * VendorSaveCategories.php
 *  *
 *  * @file        VendorSaveCategories.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

/**
 * *
 *  * VendorSaveCategories.php
 *  *
 *  * @file        VendorSaveCategories.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Observer\Vendor;

use Vnecoms\VendorsCategory\Model\Category;
use Magento\Framework\Event\ObserverInterface;

/**
 * event for prepare save, see in Save Action
 * Class CategorySaveRewritesHistorySetterObserver.
 */
class VendorSaveCategories implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsCategory\Api\CategoryLinkManagementInterface
     */
    protected $categoryLinkManagement;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    protected $setup;

    public function __construct(
        \Vnecoms\VendorsCategory\Api\CategoryLinkManagementInterface $categoryLinkManagement,
        \Magento\Framework\App\ResourceConnection $connection,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $setup
    ) {
        $this->_resource = $connection;
        $this->setup = $setup;
        $this->categoryLinkManagement = $categoryLinkManagement;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var Category $category */
        $product = $observer->getEvent()->getProduct();
        // delete other vendor category of this product
        $vendorsCategory = $this->getVesCategoryIds($product);

        if($vendorsCategory && count($vendorsCategory)){
            $connection = $this->_resource->getConnection();
            foreach ($vendorsCategory as $key => $cateId) {

                $select = $connection->select()->from(
                    $this->setup->getTable('ves_vendorscategory_category'),
                    'vendor_id'
                )->where(
                    'category_id = ?',
                    (int) $cateId
                );
                $result = $connection->fetchCol($select);

                if(!in_array($product->getVendorId(), $result)){
                    $table = $this->setup->getTable('ves_vendorscategory_category_product');
                    $sql = "DELETE FROM {$table} WHERE category_id = :category_id AND product_id = :product_id";
                    $bind = [
                        'category_id' => $cateId,
                        'product_id' => $product->getId(),
                    ];

                    $connection->query($sql, $bind);
                    unset($vendorsCategory[$key]);
                }

            }
        }

        // process assing category to product

        $vendorsCategory = is_array($vendorsCategory) ? $vendorsCategory : [];

        $this->categoryLinkManagement->assignProductToCategories(
            $product->getSku(),
            $vendorsCategory
        );
    }

    public function getVesCategoryIds($product)
    {
        if (!$product->hasData('ves_category_ids')) {
            $connection = $this->_resource->getConnection();

            $select = $connection->select()->from(
                $this->setup->getTable('ves_vendorscategory_category_product'),
                'category_id'
            )->where(
                'product_id = ?',
                (int) $product->getId()
            );

            $result = $connection->fetchCol($select);

            return $result;
        }

        if (!$product->getData('ves_category_ids')) return null;
        return explode(',', $product->getData('ves_category_ids'));
    }
}
