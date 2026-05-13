<?php

namespace Vnecoms\VendorsCredit\Model\ResourceModel\Credit\Transaction\Grid;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Psr\Log\LoggerInterface as Logger;

/**
 * App page collection
 */
class Collection extends \Vnecoms\Credit\Model\ResourceModel\Credit\Transaction\Grid\Collection
{
    protected function _construct()
    {
        parent::_construct();
        $fields = [
            'vendor_identifier' => 'vendor_id',
        ];
        foreach ($fields as $alias => $field) {
            $this->addFilterToMap(
                $alias,
                'vendor.'.$field
            );
        }
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
            ['vendor_user'=>$this->getTable('ves_vendor_user')],
            'vendor_user.customer_id = main_table.customer_id AND is_super_user = 1',
            ['is_super_user'=>'is_super_user','vendor_user_id'=>'vendor_id']
        );
        $this->getSelect()->joinLeft(
            ['vendor'=>$this->getTable('ves_vendor_entity')],
            'vendor.entity_id = vendor_user.vendor_id',
            ['vendor_identifier'=>'vendor_id']
        );

        return $this;
    }
}
