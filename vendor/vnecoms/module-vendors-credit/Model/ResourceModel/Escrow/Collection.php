<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCredit\Model\ResourceModel\Escrow;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * App page collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'escrow_id';


    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsCredit\Model\Escrow', 'Vnecoms\VendorsCredit\Model\ResourceModel\Escrow');
        $fields = [
            'status',
            'created_at',
            'updated_at',
            'vendor_id'
        ];
        foreach ($fields as $field) {
            $this->addFilterToMap(
                $field,
                'main_table.'.$field
            );
        }

        $this->addFilterToMap(
            "vendor",
            'vendor.vendor_id'
        );

        $this->addFilterToMap(
            "increment_id",
            'invoice.increment_id'
        );
    }

    /**
     * Init collection select
     *
     * @return \Vnecoms\VendorsCredit\Model\ResourceModel\Escrow\Grid\Collection
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->join(
            ['vendor'=>$this->getTable('ves_vendor_entity')],
            'vendor.entity_id = main_table.vendor_id',
            ['vendor' => 'vendor_id'],
            null,
            'left'
        );
        $this->join(
            ['vendor_invoice'=>$this->getTable('ves_vendor_sales_invoice')],
            'vendor_invoice.entity_id = main_table.relation_id',
            ['invoice_id' => 'invoice_id'],
            null,
            'left'
        );
        $this->join(
            ['invoice'=>$this->getTable('sales_invoice')],
            'vendor_invoice.invoice_id = invoice.entity_id',
            ['increment_id' => 'increment_id'],
            null,
            'left'
        );
        return $this;
    }
}
