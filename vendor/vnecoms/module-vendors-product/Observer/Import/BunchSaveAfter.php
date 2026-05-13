<?php

namespace Vnecoms\VendorsProduct\Observer\Import;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ObserverInterface;

class BunchSaveAfter implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * BunchSaveAfter constructor.
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }


    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('catalog_product_entity');
        $bunch = $observer->getBunch();
        foreach($bunch as $rowData){
            if(!isset($rowData['sku']) || !$rowData['sku']) continue;
            if(!isset($rowData['vendor_id'])) continue;
            $connection->update($tableName, ['vendor_id' => $rowData['vendor_id']], ['sku = ?' => $rowData['sku']]);
        }
    }
}
