<?php

namespace Vnecoms\Quotation\Model;

use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;
use Vnecoms\Quotation\Model\Session as QuoteSession;
use Magento\Customer\Model\Url as CustomerUrlManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class QuoteConfigProvider
{
    /**
     * @var QuoteSession
     */
    private $session;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CustomerUrlManager
     */
    private $customerUrlManager;

    /**
     * @var HttpContext
     */
    private $httpContext;

    private $urlBuilder;

    protected $storeManager;

    protected $addressMapper;

    protected $addressConfig;

    protected $formKey;

    protected $scopeConfig;

    protected $helper;

    protected $url;

    public function __construct
    (
        QuoteSession $session,
        CustomerRepository $customerRepository,
        CustomerSession $customerSession,
        CustomerUrlManager $customerUrlManager,
        HttpContext $httpContext,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        FormKey $formKey,
        \Magento\Framework\View\ConfigInterface $viewConfig,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        \Vnecoms\Quotation\Helper\Data $helper,
        \Magento\Framework\UrlInterface $url
    )
    {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->customerUrlManager = $customerUrlManager;
        $this->httpContext = $httpContext;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->formKey = $formKey;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->session = $session;
        $this->helper = $helper;
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $output['formKey'] = $this->formKey->getFormKey();
        $output['customerData'] = $this->getCustomerData();
        $output['isCustomerLoggedIn'] = $this->isCustomerLoggedIn();
        $output['storeCode'] = $this->getStoreCode();
        $output['isGuestCheckoutAllowed'] = $this->isGuestCheckoutAllowed();
        $output['isCustomerLoginRequired'] = $this->isCustomerLoginRequired();
        $output['registerUrl'] = $this->getRegisterUrl();
        $output['checkoutUrl'] = $this->getCheckoutUrl();
        $output['defaultSuccessPageUrl'] = $this->getDefaultSuccessPageUrl();
        $output['pageNotFoundUrl'] = $this->pageNotFoundUrl();
        $output['forgotPasswordUrl'] = $this->getForgotPasswordUrl();
        $output['staticBaseUrl'] = $this->getStaticBaseUrl();
        $output['uploadConfig'] = $this->getUploadConfig();
        return $output;
    }

    /**
     * Retrieve customer data
     *
     * @return array
     */
    private function getCustomerData()
    {
        $customerData = [];
        if ($this->isCustomerLoggedIn()) {
            $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
            $customerData = $customer->__toArray();
            foreach ($customer->getAddresses() as $key => $address) {
                $customerData['addresses'][$key]['inline'] = $this->getCustomerAddressInline($address);
            }
        }
        return $customerData;
    }

    /**
     * Set additional customer address data
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    private function getCustomerAddressInline($address)
    {
        $builtOutputAddressData = $this->addressMapper->toFlatArray($address);
        return $this->addressConfig
            ->getFormatByCode(\Magento\Customer\Model\Address\Config::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($builtOutputAddressData);
    }

    /**
     * Retrieve customer registration URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getRegisterUrl()
    {
        return $this->customerUrlManager->getRegisterUrl();
    }

    /**
     * Retrieve checkout URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getCheckoutUrl()
    {
        return $this->urlBuilder->getUrl('quotation');
    }

    /**
     * Retrieve checkout URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function pageNotFoundUrl()
    {
        return $this->urlBuilder->getUrl('quotation/noroute');
    }

    /**
     * Retrieve default success page URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getDefaultSuccessPageUrl()
    {
        return $this->urlBuilder->getUrl('quotation/quote/success/');
    }

    /**
     * Retrieve store code
     *
     * @return string
     * @codeCoverageIgnore
     */
    private function getStoreCode()
    {
        return $this->getStore()->getCode();
    }

    public function getStore()
    {
        return $this->session->getQuote()->getStore();
    }

    /**
     * Check if guest checkout is allowed
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isGuestCheckoutAllowed()
    {
        return (bool) !$this->helper->requireCustomerLogin($this->getStore()->getId());
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * Check if customer must be logged in to proceed with checkout
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isCustomerLoginRequired()
    {
        return (bool) $this->helper->requireCustomerLogin($this->getStore()->getId());
    }

    /**
     * Return forgot password URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    private function getForgotPasswordUrl()
    {
        return $this->customerUrlManager->getForgotPasswordUrl();
    }

    /**
     * Return base static url.
     *
     * @return string
     * @codeCoverageIgnore
     */
    protected function getStaticBaseUrl()
    {
        return $this->session->getQuote()->getStore()->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
    }

    protected function getUploadConfig()
    {
        return [
            'url' => $this->url->getUrl('quotation/message/upload'),
            'acceptFileTypes' => explode(',',$this->helper->getAllowExtensions()),
            'maxFileSize' => $this->helper->getMaxSize(), //by bytes
            'maxFileNumber' => $this->helper->getMaxNumber() //default 5 files
        ];
    }
}