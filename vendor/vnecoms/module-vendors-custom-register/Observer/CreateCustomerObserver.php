<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsCustomRegister\Observer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\RequestHandlerInterface;

/**
 * CreateCustomerObserver
 */
class CreateCustomerObserver implements ObserverInterface
{
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var IsCaptchaEnabledInterface
     */
    private $isCaptchaEnabled;

    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Vnecoms\VendorsCustomRegister\Helper\Process
     */
    protected $helperProcess;

    /**
     * CreateCustomerObserver constructor.
     * @param UrlInterface $url
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param RequestHandlerInterface $requestHandler
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Vnecoms\VendorsCustomRegister\Helper\Process $helperProcess
     */
    public function __construct(
        UrlInterface $url,
        IsCaptchaEnabledInterface $isCaptchaEnabled,
        RequestHandlerInterface $requestHandler,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Vnecoms\VendorsCustomRegister\Helper\Process $helperProcess
    ) {
        $this->url = $url;
        $this->isCaptchaEnabled = $isCaptchaEnabled;
        $this->requestHandler = $requestHandler;
        $this->scopeConfig = $scopeConfig;
        $this->helperProcess =$helperProcess;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $key = 'customer_create';
        $controller = $observer->getControllerAction();
        $request = $controller->getRequest();

        if ($request->getParam("is_seller")) {
            $request =  $this->helperProcess->processBeforeRequest($request);
        }


        if ($this->isCaptchaEnabled->isCaptchaEnabledFor($key)) {
            /** @var Action $controller */
            $response = $controller->getResponse();

            if ($request->getParam("is_seller")) {
                $redirectOnFailureUrl = $this->url->getUrl('marketplace/seller/register', ['_secure' => true]);
            } else {
                $redirectOnFailureUrl = $this->url->getUrl('*/*/create', ['_secure' => true]);
            }


            $this->requestHandler->execute($key, $request, $response, $redirectOnFailureUrl);
        }
    }
}
