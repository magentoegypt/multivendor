<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsCoupon\Model\ResourceModel;

/**
 * Cms page mysql resource
 */
class Coupon extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ves_vendor_coupon', 'coupon_id');
    }
    
    /**
     * Check if code exists
     *
     * @param string $code
     * @return bool
     */
    public function exists($code)
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from($this->getMainTable(), 'code');
        $select->where('code = :code');

        if ($connection->fetchOne($select, ['code' => $code]) !== false) {
            return true;
        }
        
        /*Make sure the code is not in salesrule_coupon table*/
        $select1 = $connection->select();
        $select1->from($this->getTable('salesrule_coupon'), 'code');
        $select1->where('code = :code');
        
        return $connection->fetchOne($select1, ['code' => $code]) !== false;
        
    }
}
