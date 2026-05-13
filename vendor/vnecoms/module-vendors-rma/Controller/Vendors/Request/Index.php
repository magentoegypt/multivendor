<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Request;


class Index extends \Vnecoms\VendorsRMA\Controller\Vendors\Vendors
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_request';
    /**
     * @return void
     */
    public function execute()
    {
        $this->getRequest()->setParam('vendor_id',$this->_session->getVendor()->getId());
        $this->_initAction();
        $this->setActiveMenu('Vnecoms_Vendors::rma_request');
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("RMA"));
        $title->prepend(__("RMA - Requests"));
        $this->_view->renderLayout();
    }
}
