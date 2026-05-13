<?php
/**
 * Get related products grid and serializer block
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

class Grid extends Action
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('customer_id');
        $model = $this->_objectManager->create('Magento\Customer\Model\Customer');
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This customer no longer exists.'));
                $this->_redirect('');
                return;
            }
            $this->_coreRegistry->register('customer_email', $model->getEmail());
        } else {
            $id = $this->getRequest()->getParam('request_id');
            $model = $this->_objectManager->create('Vnecoms\RMA\Model\Request');
            $model->load($id);

            if (!$model->getId()) {
                $this->messageManager->addError(__('This request no longer exists.'));
                $this->_redirect('');
                return;
            }
            $this->_coreRegistry->register('customer_email', $model->getCustomerEmail());
            $this->_coreRegistry->register('request_id', $model->getId());
        }

        //var_dump($model->getId());exit;
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->getLayout()->getBlock('rma.request.edit.tab.related');
        return $resultLayout;
    }
}
