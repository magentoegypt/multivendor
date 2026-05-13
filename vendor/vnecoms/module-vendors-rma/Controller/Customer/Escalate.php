<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Vnecoms\RMA\Controller\Customer\RmaViewAuthorizationInterface as RmaViewAuthorizationInterface;

class Escalate extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var RequestFactory
     */
    protected $_requestFactory;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var RmaViewAuthorizationInterface
     */
    protected $rmaAuthorization;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlinterface;

    /**
     * Escalate constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     * @param RmaViewAuthorizationInterface $rmaAuthorization
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Registry $registry,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        RmaViewAuthorizationInterface $rmaAuthorization
    ) {
        $this->rmaAuthorization = $rmaAuthorization;
        $this->resultPageFactory = $resultPageFactory;
        $this->_session = $session;
        $this->_coreRegistry  = $registry;
        $this->_urlinterface = $context->getUrl();
        $this->_requestFactory = $requestFactory->create();
        parent::__construct($context);
    }



    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        /** @var \Magento\Framework\View\Result\Page $resultPage */

        $id = $this->getRequest()->getParam('id');


        $model = $this->_requestFactory->load($id);
        if (!$model->getId() || !$this->rmaAuthorization->canView($model) || !$id) {
            $this->messageManager->addError(__('This RMA no longer exists.'));
            $this->_redirect('*/*');
            return;
        }


        $this->_coreRegistry->register('current_request', $model);
        $resultPage = $this->resultPageFactory->create();
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('rma/customer');
        }
        $resultPage->getConfig()->getTitle()->set(__('[#').$model->getIncrementId()."]".__('Escalate'));
        return $resultPage;
    }

}
