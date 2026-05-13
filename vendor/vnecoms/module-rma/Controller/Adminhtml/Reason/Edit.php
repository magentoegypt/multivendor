<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Reason;

class Edit extends Reasons
{
    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('reason_id');
        $model = $this->_objectManager->create('Vnecoms\RMA\Model\Reason');
        $this->_coreRegistry->register('current_reason', $model);
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This Reason no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Manage Reason'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getTitle() : __('New Reason')
        );

        $breadcrumb = $id ? __('Edit Reason') : __('New Reason');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }
}
