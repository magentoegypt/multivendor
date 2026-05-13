<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Status;

class Edit extends AbstractStatus
{
    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('status_id');
        $model = $this->_objectManager->create('Vnecoms\RMA\Model\Status');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This Status no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }

    
        $this->_coreRegistry->register('current_status', $model);

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Manage Status'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getTitle() : __('New Status')
        );

        $breadcrumb = $id ? __('Edit Status') : __('New Status');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }
}
