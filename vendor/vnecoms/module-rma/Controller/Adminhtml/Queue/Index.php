<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Queue;

use Vnecoms\RMA\Controller\Adminhtml\Queue\Queue;

class Index extends Queue
{
    /**
     * @return void
     */
    public function execute()
    {

        $this->_initAction()->_addBreadcrumb(__('RMA'), __('Notify Queue'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('RMA - Notify Queue'));
        $this->_view->renderLayout();
    }
}
