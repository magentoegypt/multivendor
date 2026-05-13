<?php

namespace Vnecoms\RMA\Block\Frontend\Guest\NewRequest;

class Info extends \Vnecoms\RMA\Block\Frontend\Customer\NewRequest\Info
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

    /**
     * get Current Request Data
     * @return mixed
     */
    public function getRequestData()
    {
        $requestData = $this->_customerSession->getRequestData();
        $request = $this->_coreRegistry->registry('request_rma');
        $data = $request ? $request : $requestData;
        return $data;
    }

    /**
     * get Url load product from order Id
     * @return mixed
     */
    public function getUrlFindProduct()
    {
        return $this->getUrl('vrma/guest/ajaxproduct');
    }
}
