<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Vnecoms\Quotation\Model\Quote;

abstract class AbstractQuote extends \Magento\Backend\Block\Widget
{
    /**
     * Session quote
     *
     * @var \Vnecoms\Quotation\Model\Backend\Session
     */
    protected $_sessionQuote;

    /**
     * Quote create
     *
     * @var \Vnecoms\Quotation\Model\Quote
     */
    protected $_quoteCreate;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\Quotation\Model\Backend\Session $sessionQuote,
        \Vnecoms\Quotation\Model\Quote $quoteCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_sessionQuote = $sessionQuote;
        $this->_quoteCreate = $quoteCreate;
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve create quote model object
     *
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuoteModel()
    {
        return $this->_quoteCreate;
    }

    /**
     * Retrieve quote session object
     *
     * @return \Vnecoms\Quotation\Model\Backend\Session
     */
    protected function _getSession()
    {
        return $this->_sessionQuote;
    }

    /**
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getCurrentQuote()
    {
        return $this->_registry->registry('current_quote');
    }

    /**
     * @return bool
     */
    public function isQuoteEditPage()
    {
        return (bool) $this->getRequest()->getParam('quote_id');
    }

    /**
     * Retrieve quote model object
     *
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuote()
    {
        return $this->_registry->registry('current_quote');
    }

    /**
     * Real quote for all edit and create page
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getRealQuote()
    {
        return ($this->isQuoteEditPage()) ? $this->getCurrentQuote() : $this->getQuote();
    }

    /**
     * Retrieve customer identifier
     *
     * @return int
     */
    public function getCustomerId()
    {
        if ($this->isQuoteEditPage()) return $this->getQuote()->getCustomerId();
        return $this->_getSession()->getCustomerId();
    }

    /**
     * Retrieve store model object
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        if ($this->isQuoteEditPage()) return $this->getCurrentQuote()->getStore();
        return $this->_getSession()->getStore();
    }

    /**
     * Retrieve store identifier
     *
     * @return int
     */
    public function getStoreId()
    {
        if ($this->isQuoteEditPage()) return $this->getCurrentQuote()->getStoreId();
        return $this->_getSession()->getStoreId();
    }

    /**
     * Retrieve formated price
     *
     * @param float $value
     * @return string
     */
    public function formatPrice($value)
    {
        return $this->priceCurrency->format(
            $value,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->getCurrentQuote()->getStore(),
            $this->getCurrentQuote()->getCurrencyCode()
        );
    }

    /**
     * Convert price
     *
     * @param float $value
     * @param bool $format
     * @return float
     */
    public function convertPrice($value, $format = true)
    {
        return $format
            ? $this->priceCurrency->convertAndFormat(
                $value,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $this->getCurrentQuote()->getStore(),
                $this->getCurrentQuote()->getCurrencyCode()
            )
            : $this->priceCurrency->convert($value, $this->getStore());
    }
    
    /**
     * Is edit able
     * @return bool
     */
    public function isEdiable(){
        return in_array(
            $this->getRealQuote()->getStatus(),
            [
                Quote::STATUS_CREATED,
                Quote::STATUS_CREATED_NOT_SENT,
                Quote::STATUS_PROCESSING,
                quote::STATUS_REJECTED
            ]
        );
        
    }
}
