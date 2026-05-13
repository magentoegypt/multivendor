<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsReport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const XML_PROCESS_ORDERS = 'vendors/reports/process_order';
    const XML_ORDER_DATE_FILTER_FIELD = 'vendors/reports/order_datefilter_field';
    
    /**
     * Get Process Orders Status
     * @return array:
     */
    public function getProcessOrders()
    {
        $processOrders = $this->scopeConfig->getValue(self::XML_PROCESS_ORDERS);
        
        return explode(",", $processOrders);
    }
    
    /**
     * Get Date filter field
     *
     * @return string
     */
    public function getDateFilterField()
    {
        return $this->scopeConfig->getValue(self::XML_ORDER_DATE_FILTER_FIELD);
    }
}
