<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Adminhtml\Email;

class Edit extends Template
{
    /**
     * Edit transactional email action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $template = $this->_initTemplate('id');
        $this->_setActiveMenu('Magento_Sales::sales');
        $this->_addBreadcrumb(__('Transactional Emails'), __('Transactional Emails'), $this->getUrl('adminhtml/*'));

        if ($this->getRequest()->getParam('id')) {
            $this->_addBreadcrumb(__('Edit Template'), __('Edit System Template'));
        } else {
            $this->_addBreadcrumb(__('New Template'), __('New System Template'));
        }
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Email Templates'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $template->getId() ? $template->getTemplateCode() : __('New Template')
        );

        $this->_addContent(
            $this->_view->getLayout()->createBlock(
                'Vnecoms\VendorsRMA\Block\Adminhtml\Template\Edit',
                'template_edit'
            )->setEditMode(
                (bool)$this->getRequest()->getParam('id')
            )
        );
        $this->_view->renderLayout();
    }
}
