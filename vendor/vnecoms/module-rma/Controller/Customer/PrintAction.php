<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class PrintAction extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var RequestFactory
     */
    protected $_requestFactory;
    /**
     * @var \Magento\Sales\Controller\AbstractController\OrderLoaderInterface
     */
    protected $orderLoader;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var RmaViewAuthorizationInterface
     */
    protected $rmaAuthorization;
    /**
     * @param Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        RmaViewAuthorizationInterface $rmaAuthorization
    ) {
        $this->rmaAuthorization = $rmaAuthorization;
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry  = $registry;
        $this->_requestFactory = $requestFactory->create();
        parent::__construct($context);
    }

    /**
     * Print Order Action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        if ($id) {
            $model = $this->_requestFactory->load($id);
            if (!$model->getId() || !$this->rmaAuthorization->canView($model)) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }

        $this->_coreRegistry->register('current_request', $model);
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('print');
        return $resultPage;
    }
}
