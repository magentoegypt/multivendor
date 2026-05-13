<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:28 AM
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

class Index extends Request
{
    /**
     * @return void
     */
    public function execute()
    {

        $this->_initAction()->_addBreadcrumb(__('RMA'), __('Requests'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('RMA - Requests'));
        $this->_view->renderLayout();
    }
}
