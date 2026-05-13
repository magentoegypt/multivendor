<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:28 AM
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Response;

class Index extends Reponse
{
    /**
     * @return void
     */
    public function execute()
    {

        $this->_initAction()->_addBreadcrumb(__('RMA'), __('Response'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('RMA - Response'));
        $this->_view->renderLayout();
    }
}
