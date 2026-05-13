<?php

namespace Vnecoms\Vendors\Controller\Vendors\Logout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $session;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @param Context $context
     * @param \Vnecoms\Vendors\Model\Session $session
     */
    public function __construct(
        Context $context,
        \Vnecoms\Vendors\Model\Session $session,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->session = $session;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        return parent::__construct($context);
    }

    /**
     * Retrieve cookie manager
     *
     * @deprecated 100.1.0
     * @return PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = ObjectManager::getInstance()->get(PhpCookieManager::class);
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated 100.1.0
     * @return CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        return $this->cookieMetadataFactory;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $lastCustomerId = $this->session->getId();
        $this->session->logout()->setBeforeAuthUrl($this->_redirect->getRefererUrl())
            ->setLastCustomerId($lastCustomerId);
        if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
            $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
            $metadata->setPath('/');
            $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
        }

        return $this->_redirect('account/login');
    }

}
