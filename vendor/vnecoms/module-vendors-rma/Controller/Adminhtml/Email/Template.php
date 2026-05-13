<?php
/**
 *
 * Copyright © Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Adminhtml\Email;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;

abstract class Template extends Action
{

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * Date filter instance
     *
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $_dateFilter;

    /**
     * @var \Vnecoms\RMA\Helper\Data
     */
    protected $_helper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * Init action
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Magento_Sales::sales'
        )->_addBreadcrumb(
            __('Manage Email Template'),
            __('Manage Email Template')
        );
        return $this;
    }

    /**
     * Is access to section allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Vnecoms_VendorsRMA::rma_email_template');
    }

    /**
     * Load email template from request
     *
     * @param string $idFieldName
     * @return \Magento\Email\Model\BackendTemplate $model
     */
    protected function _initTemplate($idFieldName = 'template_id')
    {
        $id = (int)$this->getRequest()->getParam($idFieldName);
        $model = $this->_objectManager->create('Vnecoms\VendorsRMA\Model\Request\Escalate\Template');
        if ($id) {
            $model->load($id);
        }
        if (!$this->_coreRegistry->registry('email_template')) {
            $this->_coreRegistry->register('email_template', $model);
        }
        if (!$this->_coreRegistry->registry('current_email_template')) {
            $this->_coreRegistry->register('current_email_template', $model);
        }
        return $model;
    }

}
