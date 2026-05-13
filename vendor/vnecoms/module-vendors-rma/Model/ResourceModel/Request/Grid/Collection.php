<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsRMA\Model\ResourceModel\Request\Grid;


class Collection extends \Vnecoms\RMA\Model\ResourceModel\Request\Grid\Collection
{

    protected function _construct()
    {
        parent::_construct();
        $fields = [
            'status',
            'created_at',
            'updated_at',
            'website_id',
            'vendor_id',
            'entity_id',
            'increment_id'
        ];
        foreach($fields as $field){
            $this->addFilterToMap(
                $field,
                'main_table.'.$field
            );
        }

        $this->addFilterToMap(
            'vendor_identifier',
            'vendor.vendor_id'
        );
    }
    /**
     * Init collection select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['vendor'=>$this->getTable('ves_vendor_entity')],
            'vendor.entity_id = main_table.vendor_id',
            ['vendor_identifier' => 'vendor_id']
        );
        return $this;
    }
}
