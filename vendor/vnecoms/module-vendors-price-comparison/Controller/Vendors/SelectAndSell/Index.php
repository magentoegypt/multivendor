<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Controller\Vendors\SelectAndSell;


class Index extends \Vnecoms\Vendors\Controller\Vendors\Action
{

    /**
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Catalog"));
        $title->prepend(__("Select And Sell"));
        
        $this->_addBreadcrumb(__("Catalog"), __("Catalog"))->_addBreadcrumb(__("Select And Sell"), __("Select And Sell"));
        $this->_view->renderLayout();
    }
}
