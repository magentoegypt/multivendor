<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate;

class Index extends \Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate
{
    /**
     * Show Main Grid
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $this->getMessageManager()->addNotice(__('Important Note: Only one Condition is applied for the table rate. To know and choose the condition check the <a href="%1">configuration</a>',$this->getUrl("config/index/edit",array('section' => "shipping_method"))));
        $this->getRequest()->setParam('vendor_id',$this->_session->getVendor()->getId());
        $this->_initAction();
        $this->setActiveMenu('Vnecoms_VendorsShippingTableRate::shipping_table');
        $this->_addBreadcrumb(__('Manage Shipping Rates'), __('Manage Shipping Rates'));
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Shipping Table Rates"));
        $this->_view->renderLayout();
    }
}
