<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Sales Order Email order items
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Vnecoms\VendorsRMA\Block\Frontend;

class PrintRma extends \Vnecoms\RMA\Block\Frontend\PrintRma
{

    /**
     * get Status class
     * @return mixed
     */
    public function getStatusClass() {
        $class = "";
        switch ($this->getRequestRma()->getStatusObject()->getCode()){
            case \Vnecoms\RMA\Model\Request::STATUS_PENDING:
                $class= "status status-pending";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_APPROVAL:
                $class= "status status-approval";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_PACKSENT:
                $class= "status status-package_sent";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_CANCELED:
                $class= "status status-canceled";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_RECEIVED:
                $class= "status status-package_received";
                break;
            case \Vnecoms\VendorsRMA\Model\Request::STATUS_AWAITING:
                $class= "status status-awaiting";
                break;
            case \Vnecoms\VendorsRMA\Model\Request::STATUS_BEING:
                $class= "status status-being";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_RETURNED:
                $class= "status status-package_returned";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_RESOLVED:
                $class= "status status-resolved";
                break;
        }
        return $class;
    }
}