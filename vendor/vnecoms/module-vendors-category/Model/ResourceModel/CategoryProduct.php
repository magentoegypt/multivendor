<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class CategoryProduct.
 */
class CategoryProduct extends AbstractDb
{
    /**
     * Event prefix.
     *
     * @var string
     */
    protected $_eventPrefix = 'ves_category_product_resource';

    /**
     * Model initialization.
     */
    protected function _construct()
    {
        $this->_init('ves_vendorscategory_category_product', 'entity_id');
    }
}
