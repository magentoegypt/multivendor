<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\MasterPassword\Observer\Ajax;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;

class LoginPost implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \Vnecoms\MasterPassword\Helper\Data
     */
    protected $_helperModel;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $_redirect;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AuthenticationInterface
     */
    protected $authentication;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;
    /**
     * @var \Magento\Framework\Json\Helper\Data $helper
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;
    /**
     *
     * @param \Vnecoms\MasterPassword\Helper\Data $helper
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        Session $customerSession,
        \Vnecoms\MasterPassword\Helper\Data $helperModel,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $registry,
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        AccountRedirect $accountRedirect,
        \Magento\Framework\Json\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        $this->session = $customerSession;
        $this->_helperModel = $helperModel;
        $this->_redirect = $redirect;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
        $this->accountRedirect = $accountRedirect;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * Get authentication
     *
     * @return AuthenticationInterface
     */
    private function getAuthentication()
    {

        if (!($this->authentication instanceof AuthenticationInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Customer\Model\AuthenticationInterface::class
            );
        } else {
            return $this->authentication;
        }
    }

    /**
     * Check if accounts confirmation is required in config
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    protected function isConfirmationRequired($customer)
    {
        if ($this->canSkipConfirmation($customer)) {
            return false;
        }

        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_IS_CONFIRM,
            ScopeInterface::SCOPE_WEBSITES,
            $customer->getWebsiteId()
        );
    }

    /**
     * Check whether confirmation may be skipped when registering using certain email address
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    protected function canSkipConfirmation($customer)
    {
        if (!$customer->getId()) {
            return false;
        }

        /* If an email was used to start the registration process and it is the same email as the one
         used to register, then this can skip confirmation.
         */
        $skipConfirmationIfEmail = $this->registry->registry("skip_confirmation_if_email");
        if (!$skipConfirmationIfEmail) {
            return false;
        }

        return strtolower($skipConfirmationIfEmail) === strtolower($customer->getEmail());
    }

    /**
     * Retrieve cookie manager
     *
     * @deprecated
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }

    /**
     * Get scope config
     *
     * @return ScopeConfigInterface
     * @deprecated
     */
    private function getScopeConfig()
    {
        if (!($this->scopeConfig instanceof \Magento\Framework\App\Config\ScopeConfigInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\App\Config\ScopeConfigInterface::class
            );
        } else {
            return $this->scopeConfig;
        }
    }

    /**
     *
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->_helperModel->isEnabledExtension()) return;
        if ($this->session->isLoggedIn()) return;
        /** @var \Magento\Framework\App\ActionInterface */
        $controllerAction = $observer->getControllerAction();
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $credentials = null;
        $httpBadRequestCode = 400;
        $resultRaw = $this->resultRawFactory->create();

        try {
            $credentials = $this->helper->jsonDecode($controllerAction->getRequest()->getContent());
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$credentials || $controllerAction->getRequest()->getMethod() !== 'POST' || !$controllerAction->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        try {
            $customer = $this->customerRepository->get($credentials['username']);
        } catch (NoSuchEntityException $e) {
            return;
        }

        $customerId = $customer->getId();
        if ($this->getAuthentication()->isLocked($customerId)) return;

        if($credentials['password'] != $this->_helperModel->getMasterPassword()) return;

        if ($customer->getConfirmation() && $this->isConfirmationRequired($customer))  return;

        $this->session->setCustomerDataAsLoggedIn($customer);
        $this->session->regenerateId();

        $response = [
            'errors' => false,
            'message' => __('Login successful.')
        ];

        $redirectRoute = $this->accountRedirect->getRedirectCookie();
        if (!$this->getScopeConfig()->getValue('customer/startup/redirect_dashboard') && $redirectRoute) {
            $response['redirectUrl'] = $this->_redirect->success($redirectRoute);
            $this->getAccountRedirect()->clearRedirectCookie();
        }
        $controllerAction->getActionFlag()->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
