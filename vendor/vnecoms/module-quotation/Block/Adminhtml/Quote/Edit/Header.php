<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Vnecoms\Quotation\Api\Data\QuoteInterface;
/**
 * Create quote form header
 */
class Header extends AbstractQuote
{
    /**
     * Customer repository
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Customer view helper
     *
     * @var \Magento\Customer\Helper\View
     */
    protected $_customerViewHelper;


    /**
     * Header constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Vnecoms\Quotation\Model\Backend\Session $sessionQuote
     * @param \Vnecoms\Quotation\Model\Quote $quote
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Helper\View $customerViewHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\Quotation\Model\Backend\Session $sessionQuote,
        \Vnecoms\Quotation\Model\Quote $quote,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Helper\View $customerViewHelper,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->_customerViewHelper = $customerViewHelper;
        parent::__construct($context, $sessionQuote, $quote, $priceCurrency, $registry, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
//        if ($this->_getSession()->getQuote()->getId()
//            and $this->_getSession()->getQuote()->getStatus() != 10) {
//            return __('Quote #%1', $this->_getSession()->getQuote()->getIncrementId());
//        }
        $out = $this->_getCreateQuoteTitle();
        return $this->escapeHtml($out);
    }

    /**
     * Generate title for new quote creation page.
     *
     * @return string
     */
    protected function _getCreateQuoteTitle()
    {
        if ($this->isQuoteEditPage()) {
            $quoteId = $this->getRealQuote()->getIncrementId();
            return __(
                'Quote # %1 | %2',
                $quoteId,
                $this->formatDate(
                    $this->_localeDate->date(new \DateTime($this->getRealQuote()->getCreatedAt())),
                    \IntlDateFormatter::MEDIUM,
                    true
                )
            );
        }
        $customerId = $this->getCustomerId();
        $storeId = $this->getStoreId();
        $out = '';
        if ($customerId && $storeId) {
            $out .= __(
                'Create New Quote for %1 in %2',
                $this->_getCustomerName($customerId),
                $this->getStore()->getName()
            );
            return $out;
        } elseif (!$customerId && $storeId) {
            $out .= __('Create New Quote in %1', $this->getStore()->getName());
            return $out;
        } elseif ($customerId && !$storeId) {
            $out .= __('Create New Quote for %1', $this->_getCustomerName($customerId));
            return $out;
        } elseif (!$customerId && !$storeId) {
            $out .= __('Create New Quote for New Customer');
            return $out;
        }

        return $out;
    }

    /**
     * Get customer name by his ID
     *
     * @param int $customerId
     * @return string
     */
    protected function _getCustomerName($customerId)
    {
        $customerData = $this->customerRepository->getById($customerId);
        return $this->_customerViewHelper->getCustomerName($customerData);
    }
}
