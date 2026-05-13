<?php
/**
 * Get related products grid and serializer block
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Request;

class Grid extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_request';
    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
    ) {
        parent::__construct($context);
        $this->resultLayoutFactory = $resultLayoutFactory;
    }

    /**
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('request_id');
        $model = $this->_objectManager->create('Vnecoms\RMA\Model\Request');
        $model->load($id);
        if (!$model->getId()) {
            $this->messageManager->addError(__('This request no longer exists.'));
            $this->_redirect('');
            return;
        }
        $this->_coreRegistry->register('customer_email',  $model->getCustomerEmail());
        $this->_coreRegistry->register('request_id',  $model->getId());

        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->getLayout()->getBlock("vendor_rma_request.grid.container");
        return $resultLayout;
    }
}
