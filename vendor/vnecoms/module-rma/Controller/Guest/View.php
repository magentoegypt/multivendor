<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Guest;

use Vnecoms\RMA\Controller\IndexInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;

    /**
     * @var RequestFactory
     */
    protected $_requestFactory;
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var RmaViewAuthorizationInterface
     */
    protected $rmaAuthorization;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $session
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Registry $registry,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        RmaViewAuthorizationInterface $rmaAuthorization
    ) {
        $this->rmaAuthorization   = $rmaAuthorization;
        $this->resultPageFactory = $resultPageFactory;
        $this->_session = $session;
        $this->_coreRegistry  = $registry;
        $this->_urlinterface = $context->getUrl();
        $this->_requestFactory = $requestFactory->create();
        parent::__construct($context);
    }

    /**
     * Customer order history
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        /** @var \Magento\Framework\View\Result\Page $resultPage */

        $id = $this->getRequest()->getParam('id');

        $postRma = $this->_session->getPostRma();
        $model = $this->_requestFactory->load($id);
        if (!$model->getId() || !$this->rmaAuthorization->canView($model) || !$id) {
            $this->messageManager->addError(__('This RMA no longer exists.'));
            return $resultRedirect->setPath('rma/guest/list');
        }
        if (!$model->getData("is_customer_read")) {
            $model->setData("is_customer_read", 1)->save();
        }

        $this->_coreRegistry->register('current_request', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('#').$model->getIncrementId());
        return $resultPage;
    }
}
