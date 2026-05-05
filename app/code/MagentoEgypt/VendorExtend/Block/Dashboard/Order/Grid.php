<?php 
namespace MagentoEgypt\VendorExtend\Block\Dashboard\Order;

use Magento\Backend\Block\Dashboard\Grid as ParentGrid;

class Grid extends \Vnecoms\VendorsDashboard\Block\Vendors\Dashboard\Order\Grid
{
    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'increment_id',
            [
                'header' => __('ID'),
                'sortable' => false,
                'type' => 'text',
                'index' => 'increment_id'
            ]
        );
        $this->addColumn(
            'created_at',
            [
                'header' => __('Purchased Date'),
                'sortable' => false,
                'type' => 'date',
                'index' => 'created_at'
            ]
        );
        $this->addColumn(
            'customer_name',
            [
                'header' => __('Customer'),
                'sortable' => false,
                'type' => 'text',
                'index' => 'customer_name'
            ]
        );
        
        $baseCurrencyCode = $this->_storeManager->getStore(0)->getBaseCurrencyCode();

        $this->addColumn(
            'grand_total',
            [
                'header' => __('Grand Total'),
                'sortable' => false,
                'type' => 'currency',
                'currency_code' => $baseCurrencyCode,
                'index' => 'base_grand_total'
            ]
        );

        $options = [];
        foreach ($this->_statusOptions->toOptionArray() as $option) {
            $options[$option['value']] = __($option['label']);
        }
        
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'sortable' => false,
                'type' => 'options',
                'options' => $options,
                'index' => 'status'
            ]
        );
        
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);

        return ParentGrid::_prepareColumns();
    }
}