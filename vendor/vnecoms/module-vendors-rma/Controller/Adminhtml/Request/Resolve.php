<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Adminhtml\Request;
use Vnecoms\RMA\Controller\Adminhtml\Request\Request;

class Resolve extends Request
{
    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('request_id');
        $model = $this->_objectManager->create('Vnecoms\VendorsRMA\Model\Request');
        if ($id) {
            $model->load($id);
            if (!$model->getId() ) {
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
        $this->_coreRegistry->register('current_request', $model);
        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Manage RMA'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? "[#".$model->getIncrementId()."] Mark as Resolve" : __('New RMA')
        );

        $breadcrumb = $id ? __('Mark as Resolve') : __('New RMA');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }
}