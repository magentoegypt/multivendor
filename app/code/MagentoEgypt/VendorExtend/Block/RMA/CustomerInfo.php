<?php
namespace MagentoEgypt\VendorExtend\Block\RMA;

use Vnecoms\VendorsRMA\Block\Frontend\Customer\NewRequest\Info as BaseInfo;
use Magento\Sales\Model\Order;

class CustomerInfo extends BaseInfo
{
    /**
     * @return $this
     */
    public function getOrderByCustomerId()
    {
        $customerId = $this->_customerSession->getCustomerId();
        $orders = $this->_saleOrder
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter("state", Order::STATE_COMPLETE);
        return $orders;
    }
}
