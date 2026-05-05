<?php
namespace MagentoEgypt\VendorExtend\Block\RMA;

use Vnecoms\VendorsRMA\Block\Frontend\Guest\NewRequest\Info as BaseInfo;

class GuestInfo extends BaseInfo
{
    /**
     * get Order Customer ID
     * @return $this
     */
    public function getOrderByCustomerId()
    {
        $data = $this->_coreRegistry->registry('request_rma');
        $order = $this->_saleOrder->getCollection()->addFieldToFilter('increment_id', $data['order_incremental_id']);
        return $order;
    }
}
