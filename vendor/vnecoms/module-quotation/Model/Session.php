<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Vnecoms\Quotation\Model\Quote;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Session extends \Magento\Framework\Session\SessionManager
{
    /**
     * Quote instance
     *
     * @var \Vnecoms\Quotation\Model\Quote
     */
    protected $_quote;

    /**
     * Customer Data Object
     *
     * @var CustomerInterface|null
     */
    protected $_customer;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Vnecoms\Quotation\Api\QuoteRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_remoteAddress;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Vnecoms\Quotation\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @param bool
     */
    protected $isQuoteMasked;


    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Session\SidResolverInterface $sidResolver,
        \Magento\Framework\Session\Config\ConfigInterface $sessionConfig,
        \Magento\Framework\Session\SaveHandlerInterface $saveHandler,
        \Magento\Framework\Session\ValidatorInterface $validator,
        \Magento\Framework\Session\StorageInterface $storage,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Customer\Model\Session $customerSession,
        \Vnecoms\Quotation\Api\QuoteRepositoryInterface $quoteRepository,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Vnecoms\Quotation\Model\QuoteFactory $quoteFactory
    ) {
        $this->_customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;
        $this->_remoteAddress = $remoteAddress;
        $this->_eventManager = $eventManager;
        $this->_storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->quoteFactory = $quoteFactory;
        parent::__construct(
            $request,
            $sidResolver,
            $sessionConfig,
            $saveHandler,
            $validator,
            $storage,
            $cookieManager,
            $cookieMetadataFactory,
            $appState
        );
    }

    /**
     * Set customer data.
     *
     * @param CustomerInterface|null $customer
     * @return \Vnecoms\Quotation\Model\Session
     * @codeCoverageIgnore
     */
    public function setCustomerData($customer)
    {
        $this->_customer = $customer;
        return $this;
    }

    /**
     * Check whether current session has quote
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function hasQuote()
    {
        return isset($this->_quote);
    }

    /**
     * Get quote instance by current session
     *
     * @return Quote
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getQuote()
    {
        $this->_eventManager->dispatch('quotation_process_before', ['quote_session' => $this]);

        if ($this->_quote === null) {
            /**
             * @var Quote $quote
             */
            $quote = $this->quoteFactory->create();
            if ($this->getQuoteId()) {
                try {
                    $quote = $this->quoteRepository->getById($this->getQuoteId());

                    /**
                     * If current currency code of quote is not equal current currency code of store,
                     * need recalculate totals of quote. It is possible if customer use currency switcher or
                     * store switcher.
                     */
                    if ($quote->getCurrencyCode() != $this->_storeManager->getStore()->getCurrentCurrencyCode()) {
                        $quote->setStore($this->_storeManager->getStore());
                        $this->quoteRepository->save($quote->collectTotals());
                        /*
                         * We mast to create new quote object, because collectTotals()
                         * can to create links with other objects.
                         */
                        $quote = $this->quoteRepository->getById($this->getQuoteId());
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $this->setQuoteId(null);
                }
            }

            if (!$this->getQuoteId()) {
                if ($this->_customerSession->isLoggedIn() || $this->_customer) {
                    $customerId = $this->_customer
                        ? $this->_customer->getId()
                        : $this->_customerSession->getCustomerId();
                    try {
                        $quote = $this->quoteRepository->getActiveForCustomer($customerId);
                        $this->setQuoteId($quote->getId());
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {

                    }
                }
            }

            if ($this->_customer) {
                $quote->setCustomer($this->_customer);
            } elseif ($this->_customerSession->isLoggedIn()) {
                $quote->setCustomer($this->customerRepository->getById($this->_customerSession->getCustomerId()));
            }

            $quote->setStore($this->_storeManager->getStore());
            $this->_quote = $quote;
        }

        /**
         * set IP Address
         */
        $remoteAddress = $this->_remoteAddress->getRemoteAddress();
        if ($remoteAddress) {
            $this->_quote->setRemoteIp($remoteAddress);
        }

        return $this->_quote;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    protected function _getQuoteIdKey()
    {
        return 'vnecoms_quote_id_' . $this->_storeManager->getStore()->getWebsiteId();
    }

    /**
     * @param int $quoteId
     * @return void
     * @codeCoverageIgnore
     */
    public function setQuoteId($quoteId)
    {
        $this->storage->setData($this->_getQuoteIdKey(), $quoteId);
    }

    /**
     * @return int
     * @codeCoverageIgnore
     */
    public function getQuoteId()
    {
        return $this->getData($this->_getQuoteIdKey());
    }

    /**
     * Load data for customer quote and merge with current quote
     *
     * @return $this
     */
    public function loadCustomerQuote()
    {
        if (!$this->_customerSession->getCustomerId()) {
            return $this;
        }

        $this->_eventManager->dispatch('vnecoms_load_customer_quote_before', ['quote_session' => $this]);

        try {
            $customerQuote = $this->quoteRepository->getForCustomer($this->_customerSession->getCustomerId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $customerQuote = $this->quoteFactory->create();
        }
        $customerQuote->setStoreId($this->_storeManager->getStore()->getId());

        if (
            $customerQuote->getId() &&
            ($this->getQuoteId() != $customerQuote->getId())
        ) {
            if ($this->getQuoteId()) {
                $this->quoteRepository->save(
                    $customerQuote->merge($this->getQuote())->collectTotals()
                );
            }

            if ($this->_quote) {
                $this->quoteRepository->delete($this->_quote);
            }
            $this->setQuoteId($customerQuote->getId());
            $this->_quote = $customerQuote;
        } else {
            $this->quoteRepository->save($this->getQuote()->setCustomer($this->_customerSession->getCustomerDataObject()));
        }
        return $this;
    }

    /**
     * Destroy/end a session
     * Unset all data associated with object
     *
     * @return $this
     */
    public function clearQuote()
    {
        $this->_eventManager->dispatch('vnecoms_quote_destroy', ['quote' => $this->getQuote()]);
        $this->_quote = null;
        $this->setQuoteId(null);
        $this->setLastSuccessQuoteId(null);
        return $this;
    }

    /**
     * Unset all session data and quote
     *
     * @return $this
     */
    public function clearStorage()
    {
        parent::clearStorage();
        $this->_quote = null;
        return $this;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param float|null|\Magento\Framework\DataObject $params
     * @return $this
     */
    public function addProduct(\Magento\Catalog\Model\Product $product, $params)
    {
        $quote = $this->getQuote();
     //   $paramO = new \Magento\Framework\DataObject($params);
        $quote->addProduct($product, $params);
        return $this;
    }

    /**
     * @return string
     */
    public function isGuestQuote()
    {
        return $this->getQuote()->getMaskId();
    }
}
