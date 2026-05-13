<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Adminhtml quote create form block
 */
class Form extends AbstractQuote
{
    /**
     * Customer form factory
     *
     * @var \Magento\Customer\Model\Metadata\FormFactory
     */
    protected $_customerFormFactory;

    /**
     * Json encoder
     *
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * Address service
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $_localeCurrency;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;


    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Vnecoms\Quotation\Model\Backend\Session $sessionQuote
     * @param \Vnecoms\Quotation\Model\Quote $quote
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Customer\Model\Metadata\FormFactory $customerFormFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\Quotation\Model\Backend\Session $sessionQuote,
        \Vnecoms\Quotation\Model\Quote $quote,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Customer\Model\Metadata\FormFactory $customerFormFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_customerFormFactory = $customerFormFactory;
        $this->customerRepository = $customerRepository;
        $this->_localeCurrency = $localeCurrency;
        $this->addressMapper = $addressMapper;
        parent::__construct($context, $sessionQuote, $quote, $priceCurrency, $registry, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('quotation_quote_form');
    }

    /**
     * Retrieve url for loading blocks
     *
     * @return string
     */
    public function getLoadBlockUrl()
    {
        return $this->getUrl('quotation/*/loadBlock');
    }

    /**
     * Retrieve url for form submiting
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('quotation/*/save');
    }

    /**
     * Get customer selector display
     *
     * @return string
     */
    public function getCustomerSelectorDisplay()
    {
        $customerId = $this->getCustomerId();
        if ($customerId === null) {
            return 'block';
        }
        return 'none';
    }

    /**
     * Get store selector display
     *
     * @return string
     */
    public function getStoreSelectorDisplay()
    {
        $storeId = $this->getStoreId();
        $customerId = $this->getCustomerId();
        if ($customerId !== null && !$storeId) {
            return 'block';
        }
        return 'none';
    }

    /**
     * Get data selector display
     *
     * @return string
     */
    public function getDataSelectorDisplay()
    {
        if ($this->isQuoteEditPage()) return 'block';

        //else
        $storeId = $this->getStoreId();
        $customerId = $this->getCustomerId();
        if ($customerId !== null && $storeId) {
            return 'block';
        }
        return 'none';
    }

    /**
     * Get quote data jason
     *
     * @return string
     */
    public function getQuoteDataJson()
    {
        $data = [];
        if ($this->getCustomerId()) {
            $data['customer_id'] = $this->getCustomerId();
        }
        if ($this->getStoreId() !== null) {
            $data['store_id'] = $this->getStoreId();
            $currency = $this->_localeCurrency->getCurrency($this->getStore()->getCurrentCurrencyCode());
            $symbol = $currency->getSymbol() ? $currency->getSymbol() : $currency->getShortName();
            $data['currency_symbol'] = $symbol;
        }

        return $this->_jsonEncoder->encode($data);
    }
}
