<?php
namespace MagentoEgypt\VendorExtend\Model\ResourceModel\Order\Shipment\Grid;

/**
 * Vnecoms ships the shipment grid collection joining sales_shipment_grid with
 * ves_vendor_sales_order, but only registers filter maps for entity_id/order_id/
 * vendor_id. Filtering by Ship Date (created_at) then breaks because both joined
 * tables expose a created_at column. Register the missing maps so the date
 * filters resolve to main_table.
 */
class Collection extends \Vnecoms\VendorsSales\Model\ResourceModel\Order\Shipment\Grid\Collection
{
    protected function _construct()
    {
        parent::_construct();
        $this->addFilterToMap('created_at', 'main_table.created_at');
        $this->addFilterToMap('updated_at', 'main_table.updated_at');
    }
}
