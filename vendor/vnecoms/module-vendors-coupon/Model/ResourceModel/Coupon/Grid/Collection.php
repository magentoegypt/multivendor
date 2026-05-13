<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\ResourceModel\Coupon\Grid;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Psr\Log\LoggerInterface as Logger;

/**
 * App page collection
 */
class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager
    ) {
        $mainTable = 'ves_vendor_coupon';
        $resourceModel = 'Vnecoms\VendorsCoupon\Model\ResourceModel\Coupon';
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }
    
    protected function _construct()
    {
        parent::_construct();
        $this->addFilterToMap(
            'vendor',
            'vendor_tbl.vendor_id'
        );
        
        $this->addFilterToMap(
            'vendor_id',
            'main_table.vendor_id'
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
        $this->join(
            ['vendor_tbl' => $this->getTable('ves_vendor_entity')],
            'main_table.vendor_id=vendor_tbl.entity_id',
            [
                'vendor' => 'vendor_id',
            ]);
        return $this;
    }
    
}
