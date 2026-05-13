<?php
/**
 * *
 *  * AfterCommitVendor.php
 *  *
 *  * @file        AfterCommitVendor.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\Vendors\Model\Vendor;

class AfterCommitVendor implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $connection;

    protected $setup;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $connection,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $setup
    ) {
        $this->connection = $connection;
        $this->setup = $setup;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        return;
        // TODO: Implement execute() method.
        //check if exist root category in table
        $category = \Magento\Framework\App\ObjectManager::getInstance()->get('Vnecoms\VendorsCategory\Model\Category');
        $vendor = $observer->getData('vendor');

        $select = $this->connection->getConnection()->select()->from(
            $this->setup->getTable('ves_vendorscategory_category'),
            ['category_id']
        )->where(
            'level = :level'
        )->where(
            'vendor_id = :vendor_id'
        );
        $bind = ['level' => 0, 'vendor_id' => $vendor->getId()];

        //var_dump($this->connection->getConnection()->fetchOne($select, $bind));die();
        if ($this->connection->getConnection()->fetchOne($select, $bind) == false) {
            if ($vendor->getStatus() == \Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED) {
                $category->setData([
                    'real_category_id' => $vendor->getId(),
                    'name' => __('Root'),
                    'path' => $vendor->getId(),
                    'is_active' => 1,
                    'level' => 0,
                    'vendor_id' => $vendor->getId(),
                    'parent_id' => 0,
                    'status' => 1,
                    'position' => $vendor->getId(),
                    'is_hide_product' => 0,
                ])->save();

                //update path
                $category->setData('path', $category->getId())->save();
            }
        }
    }
}
