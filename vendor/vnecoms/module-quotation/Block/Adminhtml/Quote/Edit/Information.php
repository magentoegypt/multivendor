<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Eav\Model\AttributeDataFactory;


/**
 * Class Information
 * @package Vnecoms\Quotation\Block\Adminhtml\Quote\Edit
 */
class Information extends AbstractQuote
{
    /**
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $_currencyFactory;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $_localeCurrency;

    /**
     * @var array
     */
    protected $status;

    /**
     * @var \Vnecoms\Quotation\Model\Source\Status
     */
    protected $statusSource;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * Customer service
     *
     * @var \Magento\Customer\Api\CustomerMetadataInterface
     */
    protected $metadata;

    /**
     * Metadata element factory
     *
     * @var \Magento\Customer\Model\Metadata\ElementFactory
     */
    protected $_metadataElementFactory;

    /**
     * Information constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Vnecoms\Quotation\Model\Backend\Session $sessionQuote
     * @param \Vnecoms\Quotation\Model\Quote $quoteCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Framework\Locale\CurrencyInterface $currency
     * @param \Vnecoms\Quotation\Model\Source\Status $status
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Customer\Api\CustomerMetadataInterface $metadata
     * @param \Magento\Customer\Model\Metadata\ElementFactory $elementFactory
     * @param array $data
     */
    public function __construct
    (
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\Quotation\Model\Backend\Session $sessionQuote,
        \Vnecoms\Quotation\Model\Quote $quoteCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Registry $registry,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\Locale\CurrencyInterface $currency,
        \Vnecoms\Quotation\Model\Source\Status $status,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\CustomerMetadataInterface $metadata,
        \Magento\Customer\Model\Metadata\ElementFactory $elementFactory,

        array $data = []
    )
    {
        $this->_currencyFactory = $currencyFactory;
        $this->_localeCurrency = $currency;
        $this->statusSource = $status;
        $this->groupRepository = $groupRepository;
        $this->metadata = $metadata;
        $this->_metadataElementFactory = $elementFactory;
        parent::__construct($context, $sessionQuote, $quoteCreate, $priceCurrency, $registry, $data);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        if (!$this->status) {
            $this->status = $this->statusSource->getOptions();
        }
        return $this->status;
    }

    /**
     * Retrieve avilable currency codes
     *
     * @return string[]
     */
    public function getAvailableCurrencies()
    {
        $dirtyCodes = $this->getStore()->getAvailableCurrencyCodes();
        $codes = [];
        if (is_array($dirtyCodes) && count($dirtyCodes)) {
            $rates = $this->_currencyFactory->create()->getCurrencyRates(
                $this->_storeManager->getStore()->getBaseCurrency(),
                $dirtyCodes
            );
            foreach ($dirtyCodes as $code) {
                if (isset($rates[$code]) || $code == $this->_storeManager->getStore()->getBaseCurrencyCode()) {
                    $codes[] = $code;
                }
            }
        }
        return $codes;
    }

    /**
     * Retrieve curency name by code
     *
     * @param string $code
     * @return string
     */
    public function getCurrencyName($code)
    {
        return $this->_localeCurrency->getCurrency($code)->getName();
    }

    /**
     * @return bool
     */
    public function isGuest()
    {
        return (bool) !$this->getRealQuote()->getCustomerId();
    }

    /**
     * Get URL to edit the customer.
     *
     * @return string
     */
    public function getCustomerViewUrl()
    {
        if (!$this->getRealQuote()->getCustomerId()) {
            return '';
        }

        return $this->getUrl('customer/index/edit', ['id' => $this->getRealQuote()->getCustomerId()]);
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->getRealQuote()->getFirstName() . ' ' . $this->getRealQuote()->getLastName();
    }

    /**
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->getRealQuote()->getCustomerEmail();
    }

    /**
     * @return string
     */
    public function getCustomerGroupName()
    {
        if ($this->getRealQuote()) {
            $customerGroupId = $this->getRealQuote()->getCustomerGroupId();
            try {
                if ($customerGroupId !== null) {
                    return $this->groupRepository->getById($customerGroupId)->getCode();
                }
            } catch (NoSuchEntityException $e) {
                return '';
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public function getAccountEditLink()
    {
        if (!$this->getRealQuote()->getCustomerId()) {
            return '';
        }

        return $this->getUrl('customer/index/edit', ['id' => $this->getRealQuote()->getCustomerId()]);
    }

    /**
     * Return array of additional account data
     * Value is option style array
     *
     * @return array
     */
    public function getCustomerAccountData()
    {
        $accountData = [
            [
                'label' => __('Company'),
                'value' => $this->getQuote()->getCustomerCompany(),
            ],
            [
                'label' => __('Phone'),
                'value' => $this->getQuote()->getCustomerPhone(),
            ],
            [
                'label' => __('Tax/VAT'),
                'value' => $this->getQuote()->getCustomerTaxvat(),
            ],
        ];

        return $accountData;
    }

    /**
     * Find sort order for account data
     * Sort Order used as array key
     *
     * @param array $data
     * @param int $sortOrder
     * @return int
     */
    protected function _prepareAccountDataSortOrder(array $data, $sortOrder)
    {
        if (isset($data[$sortOrder])) {
            return $this->_prepareAccountDataSortOrder($data, $sortOrder + 1);
        }

        return $sortOrder;
    }

    /**
     * Get object created at date affected with object store timezone
     *
     * @param mixed $store
     * @param string $createdAt
     * @return \DateTime
     */
    public function getCreatedAtStoreDate($store, $createdAt)
    {
        return $this->_localeDate->scopeDate($store, $createdAt, true);
    }

    /**
     * Get timezone for store
     *
     * @param mixed $store
     * @return string
     */
    public function getTimezoneForStore($store)
    {
        return $this->_localeDate->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
    }

    /**
     * Get object created at date
     *
     * @param string $createdAt
     * @return \DateTime
     */
    public function getOrderAdminDate($createdAt)
    {
        return $this->_localeDate->date(new \DateTime($createdAt));
    }

    /**
     * Check if is single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->_storeManager->isSingleStoreMode();
    }

    /**
     * Get order store name
     *
     * @return null|string
     */
    public function getOrderStoreName()
    {
        if ($this->getRealQuote()) {
            $storeId = $this->getRealQuote()->getStoreId();
            if ($storeId === null) {
                $deleted = __(' [deleted]');
                return nl2br($this->getRealQuote()->getStoreName()) . $deleted;
            }
            $store = $this->_storeManager->getStore($storeId);
            $name = [$store->getWebsite()->getName(), $store->getGroup()->getName(), $store->getName()];
            return implode('<br/>', $name);
        }

        return null;
    }


    /**
     * Retrieve curency name by code
     *
     * @param string $code
     * @return string
     */
    public function getCurrencySymbol($code)
    {
        $currency = $this->_localeCurrency->getCurrency($code);
        return $currency->getSymbol() ? $currency->getSymbol() : $currency->getShortName();
    }

    /**
     * Retrieve current order currency code
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->getStore()->getCurrentCurrencyCode();
    }
}