<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Status;

class Index extends AbstractStatus
{
    /**
     * @return void
     */
    public function execute()
    {

        $this->_initAction()->_addBreadcrumb(__('RMA'), __('Status'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('RMA - Status'));
        $this->_view->renderLayout();
    }
}
