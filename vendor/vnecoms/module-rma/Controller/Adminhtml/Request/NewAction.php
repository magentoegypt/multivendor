<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

class NewAction extends Request
{
    /**
     * @return void
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if ($orderId) {
            $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $object_manager->get('\Magento\Sales\Model\Order')->load($orderId);
            $this->_coreRegistry->register('current_order', $order);
        }
        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Manage Requests'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            __('New Request')
        );
        $breadcrumb = __('New Request');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }
}
