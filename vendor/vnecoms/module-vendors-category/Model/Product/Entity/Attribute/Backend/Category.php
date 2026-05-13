<?php
/**
 * *
 *  * Category.php
 *  *
 *  * @file        Category.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

/**
 * *
 *  * Category.php
 *  *
 *  * @file        Category.php
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

/**
 * Catalog product categories backend attribute model.
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */

namespace Vnecoms\VendorsCategory\Model\Product\Entity\Attribute\Backend;

class Category extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    protected $_resource;

    protected $setup;

    public function __construct(\Magento\Framework\App\ResourceConnection $connection, \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $setup)
    {
        $this->_resource = $connection;
        $this->setup = $setup;
    }

    /**
     * Set category ids to product data.
     *
     * @param \Magento\Catalog\Model\Product $object
     *
     * @return $this
     */
    public function afterLoad($object)
    {
        $connection = $this->_resource->getConnection();

        $select = $connection->select()->from(
            $this->setup->getTable('ves_vendorscategory_category_product'),
            'category_id'
        )->where(
            'product_id = ?',
            (int) $object->getId()
        );

        $result = $connection->fetchCol($select);

        $object->setData($this->getAttribute()->getAttributeCode(), $result);

        return parent::afterLoad($object);
    }

    /**
     * before save
     * @param $object
     */
    public function beforeSave($object)
    {
        if (is_array($object->getData($this->getAttribute()->getAttributeCode()))) {
            $newValue = implode(',', $object->getData($this->getAttribute()->getAttributeCode()));
            $object->setData($this->getAttribute()->getAttributeCode(), $newValue);
        }
    }
}
