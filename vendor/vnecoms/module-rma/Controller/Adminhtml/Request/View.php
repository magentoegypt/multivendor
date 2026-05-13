<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

use Vnecoms\RMA\Controller\Adminhtml\Request\Request;

class View extends Request
{
    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('request_id');
        $model = $this->_objectManager->create('Vnecoms\RMA\Model\Request');
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getTemplateData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        if (!$model->getData("is_admin_read")) {
            $model->setData("is_admin_read", 1)->save();
        }

        $this->_coreRegistry->register('current_request', $model);
        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Manage RMA'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? "[#".$model->getIncrementId()."]".$model->getTitle() : __('New RMA')
        );

        $breadcrumb = $id ? __('View RMA') : __('New RMA');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }
}
