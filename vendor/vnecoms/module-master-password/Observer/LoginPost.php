<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\MasterPassword\Observer;

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
    protected $_helper;
       
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
     * 
     * @param \Vnecoms\MasterPassword\Helper\Data $helper
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        Session $customerSession,
        \Vnecoms\MasterPassword\Helper\Data $helper,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $registry,
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        AccountRedirect $accountRedirect
    ) {
        $this->session = $customerSession;
        $this->_helper = $helper;
        $this->_redirect = $redirect;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
        $this->accountRedirect = $accountRedirect;
        
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
        if(!$this->_helper->isEnabledExtension()) return;

        if ($this->session->isLoggedIn()) return;
        
        /** @var \Magento\Framework\App\ActionInterface */
        $controllerAction = $observer->getControllerAction();
        
        if(!$controllerAction->getRequest()->isPost()) return;
        
        $login = $controllerAction->getRequest()->getPost('login');
        if (empty($login['username']) || empty($login['password'])) return;
        
        try {
            $customer = $this->customerRepository->get($login['username']);
        } catch (NoSuchEntityException $e) {
            return;
        }
        
        $customerId = $customer->getId();
        if ($this->getAuthentication()->isLocked($customerId)) return;
        
        if($login['password'] != $this->_helper->getMasterPassword()) return;
        
        if ($customer->getConfirmation() && $this->isConfirmationRequired($customer))  return;
        
        $this->session->setCustomerDataAsLoggedIn($customer);
        $this->session->regenerateId();
        
        if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
            $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
            $metadata->setPath('/');
            $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
        }
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $area = $om->get('Magento\Framework\App\State');
        $controllerName = $controllerAction->getRequest()->getControllerName();
        if($controllerName == "seller" || $area->getAreaCode() == 'vendors'){
                $helper = \Magento\Framework\App\ObjectManager::getInstance()->get(\Vnecoms\Vendors\Helper\Data::class);
                $redirectUrl = $helper->getUrl('dashboard');
                $controllerAction->getResponse()->setRedirect($redirectUrl);
        }else{
            $redirectUrl = $this->accountRedirect->getRedirectCookie();
            if (!$this->getScopeConfig()->getValue('customer/startup/redirect_dashboard') && $redirectUrl) {
                $this->session->setIsUrlNotice($this->_actionFlag->get('', self::FLAG_IS_URLS_CHECKED));
                $controllerAction->getResponse()->setRedirect($redirectUrl);
            }else{
                $this->_redirect->redirect($controllerAction->getResponse(), 'customer/account');
            }
        }

        $controllerAction->getActionFlag()->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
        
    }
}
