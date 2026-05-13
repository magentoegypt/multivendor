<?php
namespace Vnecoms\Sms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\Sms\Model\Mobile;

class CustomerCreate implements ObserverInterface
{
    /**
     * @var \Vnecoms\Sms\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Vnecoms\Sms\Model\MobileFactory
     */
    protected $mobileFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * CustomerCreate constructor.
     * @param \Vnecoms\Sms\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Vnecoms\Sms\Model\MobileFactory $mobileFactory
     */
    public function __construct(
        \Vnecoms\Sms\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Vnecoms\Sms\Model\MobileFactory $mobileFactory
    ){
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->messageManager = $messageManager;
        $this->redirect = $redirect;
        $this->mobileFactory = $mobileFactory;
    }

    /**
     * Vendor Save After
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->helper->getCurrentGateway()) return;

        $controller = $observer->getControllerAction();
        $mobileNum = $controller->getRequest()->getParam('mobile_number');
        $request = $controller->getRequest();

        if (!$mobileNum) {
            $this->customerSession->setCustomerFormData($request->getPostValue());
            $this->messageManager->addError(__('The mobile number is a required value.'));
            $this->redirect->redirect($controller->getResponse(), 'customer/account/create');
            $request->setDispatched(true);
            $controller->getActionFlag()->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
            return;
        }

        if($this->helper->isUniqueMobileNumber()){
            /* Check if the mobile is used by other */
            $collection = $this->customerCollectionFactory->create()
                ->addAttributeToFilter('mobilenumber', $mobileNum)
                ->addAttributeToFilter('entity_id', ['neq' => $this->customerSession->getId()]);

            if($collection->count()){
                $this->customerSession->setCustomerFormData($request->getPostValue());
                $this->messageManager->addError(__('The mobile number is used by another customer account.'));
                $this->redirect->redirect($controller->getResponse(), 'customer/account/create');
                $request->setDispatched(true);
                $controller->getActionFlag()->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
            }
        }

        if(
            $this->helper->isEnableVerifyingCustomerMobile() &&
            $this->helper->isEnableVerifyingOnRegister()
        ){
            $collection = $this->mobileFactory->create()->getCollection()
                ->addFieldToFilter('mobile', $mobileNum)
                ->addFieldToFilter('status', Mobile::STATUS_VERIFIED);
            if(!$collection->count()) {
                $this->customerSession->setCustomerFormData($request->getPostValue());
                $this->messageManager->addError(__('The mobile number is not verify OTP.'));
                $this->redirect->redirect($controller->getResponse(), 'customer/account/create');
                $request->setDispatched(true);
                $controller->getActionFlag()->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
            }
        }
    }
}
