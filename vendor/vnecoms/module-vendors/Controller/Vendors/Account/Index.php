<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Vendors\Controller\Vendors\Account;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends \Vnecoms\Vendors\Controller\Vendors\Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::account';
    
    /**
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__("Account"));
        $this->_addBreadcrumb(__("Account"), __("Account"));
        $vendor = $this->_session->getVendor();
        $this->_coreRegistry->register('current_vendor', $vendor);
        $this->_view->renderLayout();
    }
}
