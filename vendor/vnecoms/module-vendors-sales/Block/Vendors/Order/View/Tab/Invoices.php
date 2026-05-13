<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Block\Vendors\Order\View\Tab;

/**
 * Order Shipments grid
 *
 * @api
 * @since 100.0.2
 */
class Invoices extends \Magento\Sales\Block\Adminhtml\Order\View\Tab\Invoices
{
    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

}
