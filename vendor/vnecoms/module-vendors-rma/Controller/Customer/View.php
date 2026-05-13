<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class View extends \Magento\Customer\Controller\AbstractAccount
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
        \Vnecoms\RMA\Controller\Customer\RmaViewAuthorizationInterface $rmaAuthorization
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

        $model = $this->_requestFactory->load($id);
        if (!$model->getId() || !$this->rmaAuthorization->canView($model) || !$id) {
            $this->messageManager->addError(__('This RMA no longer exists.'));
            $this->_redirect('*/*');
            return;
        }
        if(!$model->getData("is_customer_read"))
            $model->setData("is_customer_read",1)->save();


        if($model->getEscalateObject(true)->getId() &&
            $model->getState() == \Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING) {
            $this->messageManager->addWarning(__('The vendor has escalated your RMA request to Admin,
            please <a href="%1">click here</a> to provide more evidences.',$this->_urlinterface->getUrl("*/*/escalate",
                array("id"=>$model->getId()))));
        }

        if($model->getEscalateObject()->getId() &&
            $model->getState() == \Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING) {
            $this->messageManager->addNotice(__("The RMA request is escalated, please wait for the other party's response"));
        }

        $this->_coreRegistry->register('current_request', $model);
        $resultPage = $this->resultPageFactory->create();
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('rma/customer');
        }
        $resultPage->getConfig()->getTitle()->set(__('#').$model->getIncrementId());
        return $resultPage;
    }
}
