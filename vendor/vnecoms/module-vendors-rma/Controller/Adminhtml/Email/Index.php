<?php
/**
 *
 * Copyright © Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Adminhtml\Email;

class Index extends Template
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_initAction()->_addBreadcrumb(__('RMA'), __('Email Template'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('RMA - Email Template'));
        $this->_view->renderLayout();
    }
}
