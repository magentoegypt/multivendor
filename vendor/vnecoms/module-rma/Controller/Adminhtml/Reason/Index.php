<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:28 AM
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Reason;

class Index extends Reasons
{
    /**
     * @return void
     */
    public function execute()
    {

        $this->_initAction()->_addBreadcrumb(__('RMA'), __('Reason'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('RMA - Reason'));
        $this->_view->renderLayout();
    }
}
